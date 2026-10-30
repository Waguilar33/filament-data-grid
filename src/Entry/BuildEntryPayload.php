<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use BackedEnum;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\MessageBag;
use Waguilar\FilamentDataGrid\Cells\DescribeCell;
use Waguilar\FilamentDataGrid\DataGridEntry;

final readonly class BuildEntryPayload
{
    public function __construct(private DescribeCell $describeCell) {}

    /**
     * @return array{columns: list<array{field: string, label: string, alignment: string|null, width: string|null, headerHidden: bool}>, childColumns: list<array<string, mixed>>, rows: list<array<string, mixed>>, recordsPerPage: int|null}
     */
    public function handle(DataGridEntry $entry): array
    {
        $template = $entry->getChildSchema();
        $tableColumns = array_values($entry->getTableColumns() ?? []);
        $children = $template === null ? null : $this->childRowsEntry($template);
        $columns = [];

        foreach ($this->cellComponents($template, $children) as $index => $component) {
            $columns[] = $this->describeColumn($component, $tableColumns[$index] ?? null);
        }

        $searchableFields = $entry->isSearchable()
            ? ($entry->getSearchableFields() ?? array_column($columns, 'field'))
            : [];
        $rows = [];

        foreach ($entry->getItems() as $key => $item) {
            $rows[] = $this->describeRow((string) $key, $item, $children, $searchableFields);
        }

        return [
            'columns' => $columns,
            'childColumns' => array_map(
                fn (TableColumn $column): array => $this->describeTableColumn($column),
                array_values($children?->getTableColumns() ?? []),
            ),
            'rows' => $rows,
            'recordsPerPage' => $entry->getRecordsPerPage(),
        ];
    }

    /**
     * @param  list<string>  $searchableFields
     * @return array<string, mixed>
     */
    private function describeRow(string $key, Schema $item, ?RepeatableEntry $childrenTemplate, array $searchableFields): array
    {
        $cells = [];
        $search = [];
        $children = $childrenTemplate === null ? null : $this->childRowsEntry($item);

        foreach ($this->cellComponents($item, $children) as $component) {
            $field = $this->fieldName($component);
            $cells[$field] = $this->describeCell->handle($component, new MessageBag);

            if (in_array($field, $searchableFields, true)) {
                $search[] = $this->searchText($component);
            }
        }

        return [
            'key' => $key,
            'cells' => $cells,
            'search' => mb_strtolower(implode(' ', array_filter($search, fn (string $text): bool => $text !== ''))),
            'children' => $children === null ? [] : $this->describeChildren($children),
        ];
    }

    /**
     * @return list<list<array<string, mixed>>>
     */
    private function describeChildren(RepeatableEntry $children): array
    {
        $columnCount = count($children->getTableColumns() ?? []);
        $rows = [];

        foreach ($children->getItems() as $item) {
            $components = array_slice($this->cellComponents($item, null), 0, $columnCount);

            $rows[] = array_map(
                fn (Component $component): array => $this->describeCell->handle($component, new MessageBag),
                $components,
            );
        }

        return $rows;
    }

    private function childRowsEntry(Schema $schema): ?RepeatableEntry
    {
        foreach ($schema->getComponents(withHidden: true) as $component) {
            if ($component instanceof RepeatableEntry && $component->isTable()) {
                return $component;
            }
        }

        return null;
    }

    /**
     * @return list<Component>
     */
    private function cellComponents(?Schema $schema, ?RepeatableEntry $children): array
    {
        if ($schema === null) {
            return [];
        }

        return array_values(array_filter(
            $schema->getComponents(withHidden: true),
            fn (mixed $component): bool => $component instanceof Component && $component !== $children,
        ));
    }

    /**
     * @return array{field: string, label: string, alignment: string|null, width: string|null, headerHidden: bool}
     */
    private function describeColumn(Component $component, ?TableColumn $column): array
    {
        return [
            'field' => $this->fieldName($component),
            ...($column === null
                ? ['label' => $this->text($component instanceof Entry ? $component->getLabel() : null), 'alignment' => null, 'width' => null, 'headerHidden' => false]
                : $this->describeTableColumn($column)),
        ];
    }

    /**
     * @return array{label: string, alignment: string|null, width: string|null, headerHidden: bool}
     */
    private function describeTableColumn(TableColumn $column): array
    {
        $alignment = $column->getAlignment();

        return [
            'label' => $this->text($column->getLabel()),
            'alignment' => $alignment instanceof Alignment ? $alignment->value : $alignment,
            'width' => $column->getWidth(),
            'headerHidden' => $column->isHeaderLabelHidden(),
        ];
    }

    private function fieldName(Component $component): string
    {
        return $component instanceof Entry ? $component->getName() : (string) $component->getKey(isAbsolute: false);
    }

    private function searchText(Component $component): string
    {
        if (! $component instanceof Entry) {
            return '';
        }

        $state = $component->getState();

        $texts = array_map(
            fn (mixed $value): string => match (true) {
                $component instanceof TextEntry => $this->text($this->stringable($component->formatState($value))),
                default => $this->text($this->stringable($value)),
            },
            is_array($state) ? array_values($state) : [$state],
        );

        return trim(implode(' ', array_filter($texts, fn (string $text): bool => $text !== '')));
    }

    private function stringable(mixed $value): string|Htmlable|null
    {
        return match (true) {
            $value instanceof Htmlable, is_string($value) => $value,
            $value instanceof BackedEnum => (string) $value->value,
            is_scalar($value) => (string) $value,
            default => null,
        };
    }

    private function text(string|Htmlable|null $label): string
    {
        return $label instanceof Htmlable ? strip_tags($label->toHtml()) : (string) $label;
    }
}
