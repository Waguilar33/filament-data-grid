<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

use function Filament\Support\generate_href_html;
use function Filament\Support\generate_icon_html;

final readonly class RenderRecordLink
{
    public function handle(?string $url, bool $shouldOpenInNewTab): ?string
    {
        if (blank($url)) {
            return null;
        }

        $href = generate_href_html($url, $shouldOpenInNewTab)->toHtml();
        $icon = generate_icon_html(Heroicon::ChevronRight, DataGridIconAlias::ROW_OPEN, size: IconSize::Medium)?->toHtml() ?? '';
        $label = e(__('filament-data-grid::data-grid.entry.open_record'));

        return sprintf('<a %s class="fi-data-grid-record-link" aria-label="%s" title="%s">%s</a>', $href, $label, $label, $icon);
    }
}
