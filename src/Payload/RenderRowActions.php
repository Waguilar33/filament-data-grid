<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Payload;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Waguilar33\FilamentDataGrid\DataGrid;

final class RenderRowActions
{
    public function hasActions(DataGrid $grid): bool
    {
        return $grid->isDeletable() || $grid->getExtraItemActions() !== [];
    }

    public function handle(DataGrid $grid, string $key): ?string
    {
        if ($grid->isTrailingBlankRow($key)) {
            return null;
        }

        $extraActions = array_values(array_filter(
            array_map(fn (Action $action): Action => $action(['item' => $key]), $grid->getExtraItemActions()),
            fn (Action $action): bool => $action->isVisible(),
        ));

        $deleteAction = $grid->isDeletable() ? $grid->getAction($grid->getDeleteActionName()) : null;
        $deleteAction = $deleteAction?->__invoke(['item' => $key]);

        if ($deleteAction !== null && ! $deleteAction->isVisible()) {
            $deleteAction = null;
        }

        if ($extraActions === []) {
            return $deleteAction?->toHtml();
        }

        $menuLabel = __('filament-data-grid::data-grid.actions.row_menu.label');

        return ActionGroup::make([...$extraActions, ...($deleteAction ? [$deleteAction->grouped()] : [])])
            ->label(is_string($menuLabel) ? $menuLabel : null)
            ->icon(Heroicon::EllipsisVertical)
            ->iconButton()
            ->color('gray')
            ->dropdownPlacement('bottom-end')
            ->dropdownTeleport()
            ->toHtml();
    }
}
