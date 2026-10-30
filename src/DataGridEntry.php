<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Closure;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use Waguilar\FilamentDataGrid\Entry\BuildEntryPayload;

use function Filament\Support\generate_icon_html;

class DataGridEntry extends RepeatableEntry
{
    protected int|Closure|null $recordsPerPage = null;

    /**
     * @var bool|list<string>|Closure
     */
    protected bool|array|Closure $searchable = false;

    public function paginated(int|Closure|null $recordsPerPage = 10): static
    {
        $this->recordsPerPage = $recordsPerPage;

        return $this;
    }

    public function getRecordsPerPage(): ?int
    {
        $recordsPerPage = $this->evaluate($this->recordsPerPage);

        return is_int($recordsPerPage) && $recordsPerPage > 0 ? $recordsPerPage : null;
    }

    /**
     * @param  bool|list<string>|Closure  $condition
     */
    public function searchable(bool|array|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        $searchable = $this->evaluate($this->searchable);

        return $searchable === true || (is_array($searchable) && $searchable !== []);
    }

    /**
     * @return list<string>|null
     */
    public function getSearchableFields(): ?array
    {
        $searchable = $this->evaluate($this->searchable);

        return is_array($searchable) ? array_values(array_filter($searchable, is_string(...))) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGridPayload(): array
    {
        return app(BuildEntryPayload::class)->handle($this);
    }

    public function toEmbeddedHtml(): string
    {
        $styleHref = FilamentAsset::getStyleHref('data-grid', FilamentDataGridServiceProvider::PACKAGE);

        $html = view('filament-data-grid::data-grid-entry', [
            'entry' => $this,
            'payload' => $this->getGridPayload(),
            'alpineSrc' => FilamentAsset::getAlpineComponentSrc('data-grid-entry', FilamentDataGridServiceProvider::PACKAGE),
            'styleHref' => $styleHref,
            'config' => Js::from([
                'styleHref' => $styleHref,
                'translations' => __('filament-data-grid::data-grid.entry'),
                'icons' => [
                    'expand' => generate_icon_html(Heroicon::ChevronRight)?->toHtml(),
                ],
            ]),
        ])->render();

        return $this->wrapEmbeddedHtml($html);
    }
}
