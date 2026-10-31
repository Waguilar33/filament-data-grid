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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Waguilar\FilamentDataGrid\Cells\DescribeCell;
use Waguilar\FilamentDataGrid\DataGridEntry;

final readonly class BuildEntryPayload
{
    public function __construct(private DescribeCell $describeCell) {}

    /**
     * @param  array<array-key, Schema>  $items
     * @return array<string, mixed>
     */
    public function handle(DataGridEntry $entry, array $items, ?int $total, int $page): array
    {
        $template = $entry->getChildSchema();
        $tableColumns = array_values($entry->getTableColumns() ?? []);
        $children = $template === null ? null : $this->childRowsEntry($template);
        $columns = [];

        foreach ($this->cellComponents($template, $children) as $index => $component) {
            $columns[] = $this->describeColumn($component, $tableColumns[$index] ?? null);
        }

        return [
            'columns' => $columns,
            'childColumns' => array_map(
                fn (TableColumn $column): array => $this->describeTableColumn($column),
                array_values($children?->getTableColumns() ?? []),
            ),
            'rows' => $this->rows($entry, $items),
            'childTable' => $entry->hasChildTable(),
            'childTablePadding' => $entry->getChildTablePadding(),
            'serverSide' => $entry->isServerSide(),
            'totals' => $this->describeTotals($entry),
            'total' => $total,
            'page' => $page,
            'filters' => $entry->getEntryFilters()->describe(),
            'recordsPerPageOptions' => $entry->getPaginationPageOptions(),
            'recordsPerPage' => $entry->getDefaultPaginationPageOption(),
        ];
    }

    /**
     * @param  array<array-key, Schema>  $items
     * @return list<array<string, mixed>>
     */
    public function rows(DataGridEntry $entry, array $items): array
    {
        $rows = [];

        foreach ($items as $key => $item) {
            $rows[] = $this->describeRow($entry, (string) $key, $item);
        }

        return $rows;
    }

    public function searchText(DataGridEntry $entry, Schema $item): string
    {
        if (! $entry->isSearchable()) {
            return '';
        }

        $fields = $entry->getSearchableFields();
        $children = $this->childRowsEntry($item);
        $texts = [];

        foreach ($this->cellComponents($item, $children) as $component) {
            if ($fields === null || in_array($this->fieldName($component), $fields, true)) {
                $texts[] = $this->componentSearchText($component);
            }
        }

        return mb_strtolower(implode(' ', array_filter($texts, fn (string $text): bool => $text !== '')));
    }

    /**
     * @return array<string, mixed>
     */
    private function describeRow(DataGridEntry $entry, string $key, Schema $item): array
    {
        $cells = [];
        $components = [];
        $children = $this->childRowsEntry($item);

        foreach ($this->cellComponents($item, $children) as $component) {
            $components[$this->fieldName($component)] = $component;
            $cells[$this->fieldName($component)] = $this->describeCell->handle($component, new MessageBag);
        }

        return [
            'key' => $key,
            'cells' => $cells,
            'totals' => $this->totalValues($entry, $components, $cells),
            'search' => $entry->isServerSide() ? '' : $this->searchText($entry, $item),
            'filterValues' => $entry->isServerSide() ? [] : $entry->getEntryFilters()->rawValues($item),
            'children' => $children === null ? [] : $this->describeChildren($children),
            'above' => $entry->renderExpandedAbove($item),
            'below' => $entry->renderExpandedBelow($item),
            'height' => $entry->getRowHeight($entry->hasRowHeightPerRow() ? $this->rawState($item) : []),
            ...$entry->describeRecord($key, $item),
        ];
    }

    /**
     * @return array{label: string|null, labelAlignment: string|null, columns: array<string, array<string, mixed>>}|null
     */
    private function describeTotals(DataGridEntry $entry): ?array
    {
        $totals = $entry->getTotals();

        if ($totals === []) {
            return null;
        }

        $columns = [];

        foreach ($totals as $total) {
            $columns[$total->getField()] = $total->describe($entry);
        }

        return ['label' => $entry->getTotalsLabel(), 'labelAlignment' => $entry->getTotalsLabelAlignment(), 'columns' => $columns];
    }

    /**
     * @param  array<string, Component>  $components
     * @param  array<string, array<string, mixed>>  $cells
     * @return array<string, float>
     */
    private function totalValues(DataGridEntry $entry, array $components, array $cells): array
    {
        $values = [];

        foreach ($entry->getTotals() as $total) {
            $field = $total->getField();
            $component = $components[$field] ?? null;
            $state = $component instanceof Entry && ($cells[$field]['visible'] ?? true) !== false ? $component->getState() : null;

            $values[$field] = is_numeric($state) ? (float) $state : 0.0;
        }

        return $values;
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

    /**
     * @return array<array-key, mixed>
     */
    private function rawState(Schema $item): array
    {
        $state = $item->getRecord(withParentComponentRecord: false) ?? $item->getConstantState();

        return match (true) {
            $state instanceof Model => $state->toArray(),
            is_object($state) => get_object_vars($state),
            default => $state,
        };
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

    private function componentSearchText(Component $component): string
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
