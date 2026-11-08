<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry\Concerns;

use Closure;

trait HasLoadingSkeleton
{
    private const LIVEWIRE_LOADING_DELAYS = [
        'none' => 0,
        'shortest' => 50,
        'shorter' => 100,
        'short' => 150,
        'default' => 200,
        'long' => 300,
        'longer' => 500,
        'longest' => 1000,
    ];

    protected bool|Closure|null $hasLoadingSkeleton = null;

    protected bool $hasTableLoadingSkeleton = false;

    public function loadingSkeleton(bool|Closure $condition = true): static
    {
        $this->hasLoadingSkeleton = $condition;

        return $this;
    }

    public function hasLoadingSkeleton(): bool
    {
        return (bool) ($this->evaluate($this->hasLoadingSkeleton) ?? $this->hasTableLoadingSkeleton);
    }

    public function getLoadingSkeletonDelay(): int
    {
        $delay = config('filament.livewire_loading_delay', 'default');

        return self::LIVEWIRE_LOADING_DELAYS[is_string($delay) ? $delay : 'default'] ?? self::LIVEWIRE_LOADING_DELAYS['default'];
    }
}
