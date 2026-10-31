<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Concerns;

use BackedEnum;
use Closure;
use Filament\Schemas\Components\Concerns\CanBeCompact;
use Illuminate\Contracts\Support\Htmlable;

trait HasGridHeader
{
    use CanBeCompact;

    protected string|Htmlable|Closure|null $heading = null;

    protected string|Htmlable|Closure|null $description = null;

    protected string|BackedEnum|Htmlable|Closure|null $icon = null;

    public function heading(string|Htmlable|Closure|null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): string|Htmlable|null
    {
        $heading = $this->evaluate($this->heading);

        return is_string($heading) || $heading instanceof Htmlable ? $heading : null;
    }

    public function description(string|Htmlable|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string|Htmlable|null
    {
        $description = $this->evaluate($this->description);

        return is_string($description) || $description instanceof Htmlable ? $description : null;
    }

    public function icon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        $icon = $this->evaluate($this->icon);

        return is_string($icon) || $icon instanceof BackedEnum || $icon instanceof Htmlable ? $icon : null;
    }

    public function hasGridHeader(): bool
    {
        return filled($this->getHeading()) || filled($this->getDescription()) || filled($this->getIcon());
    }
}
