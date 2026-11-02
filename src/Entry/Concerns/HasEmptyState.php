<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry\Concerns;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\View\TablesIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;

trait HasEmptyState
{
    protected string|Htmlable|Closure|null $emptyStateHeading = null;

    protected string|Htmlable|Closure|null $emptyStateDescription = null;

    protected string|BackedEnum|Htmlable|Closure|null $emptyStateIcon = null;

    /**
     * @var array<Action|ActionGroup>|Closure
     */
    protected array|Closure $emptyStateActions = [];

    /**
     * @var list<Action|ActionGroup>|null
     */
    protected ?array $cachedEmptyStateActions = null;

    public function emptyStateHeading(string|Htmlable|Closure|null $heading): static
    {
        $this->emptyStateHeading = $heading;

        return $this;
    }

    public function emptyStateDescription(string|Htmlable|Closure|null $description): static
    {
        $this->emptyStateDescription = $description;

        return $this;
    }

    public function emptyStateIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    /**
     * @param  array<Action|ActionGroup>|Closure  $actions
     */
    public function emptyStateActions(array|Closure $actions): static
    {
        $this->emptyStateActions = $actions;
        $this->cachedEmptyStateActions = null;
        $this->cachedActions = null;

        return $this;
    }

    public function getEmptyStateHeading(): string|Htmlable
    {
        $heading = $this->evaluate($this->emptyStateHeading);

        if (is_string($heading) || $heading instanceof Htmlable) {
            return $heading;
        }

        $default = __('filament-data-grid::data-grid.entry.empty_state_heading');

        return is_string($default) ? $default : '';
    }

    public function getEmptyStateDescription(): string|Htmlable|null
    {
        $description = $this->evaluate($this->emptyStateDescription);

        return is_string($description) || $description instanceof Htmlable ? $description : null;
    }

    public function getEmptyStateIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->emptyStateIcon);

        return is_string($icon) || $icon instanceof BackedEnum || $icon instanceof Htmlable
            ? $icon
            : FilamentIcon::resolve(TablesIconAlias::EMPTY_STATE) ?? Heroicon::OutlinedXMark;
    }

    /**
     * @return list<Action|ActionGroup>
     */
    public function getEmptyStateActions(): array
    {
        return $this->cachedEmptyStateActions ??= array_values(array_map(
            fn (Action|ActionGroup $action): Action|ActionGroup => $action instanceof Action
                ? $this->prepareAction($action)
                : $this->prepareEmptyStateActionGroup($action),
            array_filter(
                Arr::wrap($this->evaluate($this->emptyStateActions)),
                fn (mixed $action): bool => $action instanceof Action || $action instanceof ActionGroup,
            ),
        ));
    }

    public function hasEmptyState(): bool
    {
        return filled($this->evaluate($this->emptyStateHeading))
            || filled($this->getEmptyStateDescription())
            || filled($this->evaluate($this->emptyStateIcon))
            || $this->getEmptyStateActions() !== [];
    }

    private function prepareEmptyStateActionGroup(ActionGroup $group): ActionGroup
    {
        foreach ($group->getFlatActions() as $action) {
            $this->prepareAction($action);
        }

        return $this->prepareActionGroup($group);
    }
}
