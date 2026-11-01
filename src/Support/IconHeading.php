<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Support;

use BackedEnum;
use Filament\Support\Enums\IconSize;
use Filament\Support\View\ComponentAttributeBag;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

use function Filament\Support\generate_icon_html;

final readonly class IconHeading implements Htmlable, Stringable
{
    public function __construct(
        private string|Htmlable $heading,
        private string|BackedEnum|Htmlable $icon,
    ) {}

    public static function make(string|Htmlable $heading, string|BackedEnum|Htmlable $icon): self
    {
        return new self($heading, $icon);
    }

    public function toHtml(): string
    {
        $icon = generate_icon_html(
            $this->icon,
            attributes: (new ComponentAttributeBag)->class(['fi-data-grid-heading-icon']),
            size: IconSize::Large,
        )?->toHtml() ?? '';

        $heading = $this->heading instanceof Htmlable ? $this->heading->toHtml() : e($this->heading);

        return $icon.$heading;
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }
}
