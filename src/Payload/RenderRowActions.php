<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Payload;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Waguilar\FilamentDataGrid\DataGrid;

final class RenderRowActions
{
    public const ROW_KEY_PLACEHOLDER = '__fi_data_grid_row_key__';

    public const INLINE_ACTIONS_CLASS = 'fi-data-grid-inline-actions';

    public function hasActions(DataGrid $grid): bool
    {
        return $grid->isDeletable() || $grid->getRowActions() !== [];
    }

    public function handle(DataGrid $grid, string $key): ?string
    {
        if ($grid->isTrailingBlankRow($key)) {
            return null;
        }

        $actions = array_map(fn (Action|ActionGroup $action): Action|ActionGroup => $this->forRow($action, $key), $grid->getRowActions());

        $deleteAction = $grid->isDeletable() ? $grid->getAction($grid->getDeleteActionName()) : null;

        if ($deleteAction !== null) {
            $actions[] = $deleteAction(['item' => $key])->link();
        }

        $html = implode('', array_map(fn (Action|ActionGroup $action): string => $action->isVisible() ? $action->toHtml() : '', $actions));

        if ($html === '') {
            return null;
        }

        return $this->compact('<div class="fi-ta-actions '.self::INLINE_ACTIONS_CLASS.'">'.$html.'</div>');
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

    private function forRow(Action|ActionGroup $action, string $key): Action|ActionGroup
    {
        if ($action instanceof Action) {
            return $action(['item' => $key]);
        }

        $group = $action->getClone();

        return $group->actions(array_map(fn (Action|ActionGroup $child): Action|ActionGroup => $this->forRow($child, $key), $group->getActions()));
    }

    private function compact(?string $html): ?string
    {
        return $html === null ? null : trim((string) preg_replace('/>\s+</', '> <', $html));
    }
}
