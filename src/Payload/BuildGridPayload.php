<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Payload;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\MessageBag;
use Waguilar\FilamentDataGrid\Cells\DescribeCell;
use Waguilar\FilamentDataGrid\Cells\DescribeColumn;
use Waguilar\FilamentDataGrid\DataGrid;

final readonly class BuildGridPayload
{
    public function __construct(
        private DescribeColumn $describeColumn,
        private DescribeCell $describeCell,
        private RenderRowActions $renderRowActions,
    ) {}

    /**
     * @return array{columns: list<array<string, mixed>>, rows: list<array<string, mixed>>, hasActions: bool, actionTemplates: array<string, string>, rowKeyPlaceholder: string, frozenColumns: int, reorderable: bool, hasChildRows: bool, childTablePadding: int, highlightEditableColumns: bool|list<string>, cellIcons: array<string, string>}
     */
    public function handle(DataGrid $grid): array
    {
        $tableColumns = array_values($grid->getTableColumns() ?? []);
        $errors = $grid->getLivewire()->getErrorBag();
        $errors = $errors instanceof MessageBag ? $errors : new MessageBag;
        $rows = [];
        $actionTemplates = [];
        $cellIcons = [];

        foreach ($grid->getItems() as $key => $item) {
            $rows[] = $this->describeRow($grid, (string) $key, $item, $errors, $actionTemplates, $cellIcons);
        }

        $columns = [];

        foreach ($this->cellComponents($grid->getChildSchema()) as $index => $component) {
            $column = $this->describeColumn->handle($component, $this->tableColumn($tableColumns, $index));

            if ($this->isShownInAnyRow($component, $column['field'], $rows)) {
                $columns[] = $column;
            }
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'hasActions' => $this->renderRowActions->hasActions($grid),
            'actionTemplates' => $actionTemplates,
            'rowKeyPlaceholder' => RenderRowActions::ROW_KEY_PLACEHOLDER,
            'frozenColumns' => min($grid->getFrozenColumns(), count($columns)),
            'reorderable' => $grid->isReorderableWithDragAndDrop(),
            'hasChildRows' => $this->childGrids($grid->getChildSchema()) !== [],
            'childTablePadding' => $grid->getChildTablePadding(),
            'highlightEditableColumns' => $grid->getHighlightedEditableColumns(),
            'cellIcons' => $cellIcons,
        ];
    }

    /**
     * @param  array<string, string>  $actionTemplates
     * @param  array<string, string>  $cellIcons
     * @return array<string, mixed>
     */
    private function describeRow(DataGrid $grid, string $key, Schema $item, MessageBag $errors, array &$actionTemplates, array &$cellIcons): array
    {
        $cells = [];

        foreach ($this->cellComponents($item) as $component) {
            $cell = $this->describeCell->handle($component, $errors);

            if (is_string($cell['icon'] ?? null)) {
                $iconKey = md5($cell['icon']);
                $cellIcons[$iconKey] = $cell['icon'];
                $cell['icon'] = $iconKey;
            }

            $cells[$this->describeColumn->fieldName($component)] = $cell;
        }

        $rows = $grid->getRawState();
        $state = is_array($rows) && is_array($rows[$key] ?? null) ? $rows[$key] : [];
        $isTrailingBlank = $grid->isTrailingBlankRow($key);
        $child = $isTrailingBlank ? null : $this->visibleChildGrid($item);

        return [
            'key' => $key,
            'state' => $state,
            'cells' => $cells,
            'isTrailingBlank' => $isTrailingBlank,
            'actions' => $this->renderRowActions->template($grid, $key, $actionTemplates),
            'child' => $child === null ? null : ['config' => $child->getGridConfig(), 'payload' => $this->handle($child)],
            'hasChildErrors' => $child !== null && $this->hasErrorsUnder((string) $child->getStatePath(), $errors),
            'expanded' => ! $isTrailingBlank && $grid->shouldExpandRow($key, $state),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function isShownInAnyRow(Component $component, string $field, array $rows): bool
    {
        if ($rows === []) {
            return ! $component->isHidden();
        }

        foreach ($rows as $row) {
            $cells = $row['cells'];

            if (is_array($cells) && is_array($cells[$field] ?? null) && ($cells[$field]['visible'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    private function visibleChildGrid(Schema $item): ?DataGrid
    {
        foreach ($this->childGrids($item) as $child) {
            if ($child->isVisible()) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @return list<DataGrid>
     */
    private function childGrids(?Schema $schema): array
    {
        return array_values(array_filter(
            $schema?->getComponents(withHidden: true) ?? [],
            fn (mixed $component): bool => $component instanceof DataGrid,
        ));
    }

    private function hasErrorsUnder(string $statePath, MessageBag $errors): bool
    {
        foreach ($errors->keys() as $key) {
            if (str_starts_with($key, "{$statePath}.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Component>
     */
    private function cellComponents(?Schema $schema): array
    {
        if ($schema === null) {
            return [];
        }

        return array_values(array_filter(
            $schema->getComponents(withHidden: true),
            fn (mixed $component): bool => $component instanceof Component && ! $component instanceof Hidden && ! $component instanceof DataGrid,
        ));
    }

    /**
     * @param  list<TableColumn>  $tableColumns
     */
    private function tableColumn(array $tableColumns, int $index): ?TableColumn
    {
        return $tableColumns[$index] ?? null;
    }
}
