<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Concerns;

use Closure;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

use function Filament\Support\generate_icon_html;

trait HasExpandIconSize
{
    protected IconSize|Closure|null $expandIconSize = null;

    public function expandIconSize(IconSize|Closure|null $size): static
    {
        $this->expandIconSize = $size;

        return $this;
    }

    public function getExpandIconSize(): IconSize
    {
        $size = $this->evaluate($this->expandIconSize);

        return $size instanceof IconSize ? $size : IconSize::Small;
    }

    protected function getExpandIconHtml(): ?string
    {
        return generate_icon_html(Heroicon::ChevronRight, DataGridIconAlias::ROW_EXPAND, size: $this->getExpandIconSize())?->toHtml();
    }
}
