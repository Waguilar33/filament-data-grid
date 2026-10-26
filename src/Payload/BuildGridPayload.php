<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Payload;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\MessageBag;
use Waguilar33\FilamentDataGrid\Cells\DescribeCell;
use Waguilar33\FilamentDataGrid\Cells\DescribeColumn;
use Waguilar33\FilamentDataGrid\DataGrid;

final readonly class BuildGridPayload
{
    public function __construct(
        private DescribeColumn $describeColumn,
        private DescribeCell $describeCell,
    ) {}

    /**
     * @return array{columns: list<array<string, mixed>>, rows: list<array<string, mixed>>, hasActions: bool}
     */
    public function handle(DataGrid $grid): array
    {
        $tableColumns = array_values($grid->getTableColumns() ?? []);
        $columns = [];

        foreach ($this->cellComponents($grid->getChildSchema()) as $index => $component) {
            $columns[] = $this->describeColumn->handle($component, $this->tableColumn($tableColumns, $index));
        }

        $errors = $grid->getLivewire()->getErrorBag();
        $errors = $errors instanceof MessageBag ? $errors : new MessageBag;
        $deleteAction = $grid->isDeletable() ? $grid->getAction($grid->getDeleteActionName()) : null;
        $rows = [];

        foreach ($grid->getItems() as $key => $item) {
            $rows[] = $this->describeRow($grid, (string) $key, $item, $errors, $deleteAction);
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'hasActions' => $deleteAction !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeRow(DataGrid $grid, string $key, Schema $item, MessageBag $errors, ?Action $deleteAction): array
    {
        $cells = [];

        foreach ($this->cellComponents($item) as $component) {
            $cells[$this->describeColumn->fieldName($component)] = $this->describeCell->handle($component, $errors);
        }

        $rows = $grid->getRawState();
        $state = is_array($rows) && is_array($rows[$key] ?? null) ? $rows[$key] : [];

        return [
            'key' => $key,
            'state' => $state,
            'cells' => $cells,
            'isTrailingBlank' => $grid->isTrailingBlankRow($key),
            'actions' => $this->rowActionsHtml($grid, $key, $deleteAction),
        ];
    }

    private function rowActionsHtml(DataGrid $grid, string $key, ?Action $deleteAction): ?string
    {
        if ($deleteAction === null || $grid->isTrailingBlankRow($key)) {
            return null;
        }

        $rowDeleteAction = $deleteAction(['item' => $key]);

        return $rowDeleteAction->isVisible() ? $rowDeleteAction->toHtml() : null;
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
            fn (mixed $component): bool => $component instanceof Component && ! $component instanceof Hidden,
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
