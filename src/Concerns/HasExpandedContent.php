<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Concerns;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

trait HasExpandedContent
{
    /**
     * @var array<Component|Htmlable>|Closure|null
     */
    protected array|Closure|null $expandedAbove = null;

    /**
     * @var array<Component|Htmlable>|Closure|null
     */
    protected array|Closure|null $expandedBelow = null;

    /**
     * @param  array<Component|Htmlable>|Closure|null  $components
     */
    public function expandedAbove(array|Closure|null $components): static
    {
        $this->expandedAbove = $components;

        return $this;
    }

    /**
     * @param  array<Component|Htmlable>|Closure|null  $components
     */
    public function expandedBelow(array|Closure|null $components): static
    {
        $this->expandedBelow = $components;

        return $this;
    }

    public function hasExpandedContent(): bool
    {
        return $this->expandedAbove !== null || $this->expandedBelow !== null;
    }

    /**
     * @param  array<Component|Htmlable>|Closure|null  $slot
     * @param  array<string, mixed>  $namedInjections
     * @param  array<string, mixed>  $typedInjections
     */
    private function renderExpandedSlot(array|Closure|null $slot, Schema $item, array $namedInjections, array $typedInjections = []): ?string
    {
        if ($slot === null) {
            return null;
        }

        $components = $this->evaluate($slot, $namedInjections, $typedInjections);

        if (! is_array($components) || $components === []) {
            return null;
        }

        $components = array_values(array_map(
            fn (Component|Htmlable $component): Component|Htmlable => $component instanceof Component ? $component->getClone() : $component,
            array_filter($components, fn (mixed $component): bool => $component instanceof Component || $component instanceof Htmlable),
        ));

        $html = trim($item->getClone()->components($components)->toHtml());

        return $html === '' ? null : $html;
    }
}
