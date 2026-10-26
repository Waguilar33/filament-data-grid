<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Support\Enums\Width;
use Waguilar33\FilamentDataGrid\DataGrid;

final class BuildEditCellAction
{
    public function handle(string $name): Action
    {
        return Action::make($name)
            ->modalHeading(fn (array $arguments, DataGrid $component): ?string => $this->heading($component, $arguments))
            ->modalWidth(Width::Medium)
            ->schema(fn (array $arguments, DataGrid $component): array => array_filter([
                $this->rowField($component, $arguments)?->getClone(),
            ]))
            ->fillForm(fn (array $arguments, DataGrid $component): array => $this->rowState($component, $arguments))
            ->action(function (array $data, array $arguments, DataGrid $component): void {
                $field = $this->rowField($component, $arguments);
                $item = $arguments['item'] ?? null;
                $rows = $component->getRawState();

                if ($field === null || ! is_string($item) || ! is_array($rows) || ! is_array($rows[$item] ?? null)) {
                    return;
                }

                $rows[$item][$field->getName()] = $data[$field->getName()] ?? null;
                $component->rawState($rows);
                $field->callAfterStateUpdated();
            })
            ->visible(fn (DataGrid $component): bool => ! $component->isDisabled());
    }

    /**
     * @param  array<mixed>  $arguments
     */
    private function rowField(DataGrid $grid, array $arguments): ?Field
    {
        $item = $arguments['item'] ?? null;
        $field = $arguments['field'] ?? null;

        if (! is_string($item) || ! is_string($field)) {
            return null;
        }

        $rowField = $grid->getRowField($item, $field);

        return $rowField !== null && ! $rowField->isDisabled() && $rowField->isVisible() ? $rowField : null;
    }

    /**
     * @param  array<mixed>  $arguments
     * @return array<string, mixed>
     */
    private function rowState(DataGrid $grid, array $arguments): array
    {
        $field = $this->rowField($grid, $arguments);

        return $field === null ? [] : [$field->getName() => $field->getState()];
    }

    /**
     * @param  array<mixed>  $arguments
     */
    private function heading(DataGrid $grid, array $arguments): ?string
    {
        $label = $this->rowField($grid, $arguments)?->getLabel();

        return is_string($label) ? $label : null;
    }
}
