<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Payload;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\DataGrid;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

final class RenderRowActions
{
    public const ROW_KEY_PLACEHOLDER = '__fi_data_grid_row_key__';

    public const INLINE_ACTIONS_CLASS = 'fi-data-grid-inline-actions';

    private const MAX_INLINE_ACTIONS = 2;

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

        $actions = [...$extraActions, ...($deleteAction ? [$deleteAction] : [])];

        if ($actions === []) {
            return null;
        }

        if (count($actions) <= self::MAX_INLINE_ACTIONS) {
            $html = implode('', array_map(fn (Action $action): string => $action->link()->toHtml(), $actions));

            return $this->compact('<div class="fi-ta-actions '.self::INLINE_ACTIONS_CLASS.'">'.$html.'</div>');
        }

        $menuLabel = __('filament-data-grid::data-grid.actions.row_menu.label');

        $html = ActionGroup::make([...$extraActions, ...($deleteAction ? [$deleteAction->grouped()] : [])])
            ->label(is_string($menuLabel) ? $menuLabel : null)
            ->icon(FilamentIcon::resolve(DataGridIconAlias::ROW_ACTIONS) ?? Heroicon::EllipsisVertical)
            ->iconButton()
            ->color('gray')
            ->dropdownPlacement('bottom-end')
            ->dropdownTeleport()
            ->toHtml();

        return $this->compact($html);
    }

    /**
     * @param  array<string, string>  $templates
     */
    public function template(DataGrid $grid, string $key, array &$templates): ?string
    {
        $html = $this->handle($grid, $key);

        if ($html === null) {
            return null;
        }

        $template = strlen($key) >= 8 ? str_replace($key, self::ROW_KEY_PLACEHOLDER, $html) : $html;
        $id = md5($template);
        $templates[$id] = $template;

        return $id;
    }

    private function compact(?string $html): ?string
    {
        return $html === null ? null : trim((string) preg_replace('/>\s+</', '> <', $html));
    }
}
