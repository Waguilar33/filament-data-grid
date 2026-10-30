<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Closure;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Livewire\Attributes\Renderless;
use Traversable;
use Waguilar\FilamentDataGrid\Entry\BuildEntryPayload;
use Waguilar\FilamentDataGrid\Entry\EntryPage;

use function Filament\Support\generate_icon_html;

class DataGridEntry extends RepeatableEntry
{
    public const ALL_RECORDS = 'all';

    private const MAX_SEARCH_LENGTH = 255;

    /**
     * @var bool|int|list<int|string>|Closure
     */
    protected bool|int|array|Closure $paginationPageOptions = false;

    protected int|string|Closure|null $defaultPaginationPageOption = null;

    /**
     * @var bool|list<string>|Closure
     */
    protected bool|array|Closure $searchable = false;

    protected bool|Closure $isServerSide = false;

    protected ?Closure $getRecordsUsing = null;

    /**
     * @param  bool|int|list<int|string>|Closure  $condition
     */
    public function paginated(bool|int|array|Closure $condition = true): static
    {
        $this->paginationPageOptions = $condition;

        return $this;
    }

    public function defaultPaginationPageOption(int|string|Closure|null $option): static
    {
        $this->defaultPaginationPageOption = $option;

        return $this;
    }

    public function isPaginated(): bool
    {
        return $this->getPaginationPageOptions() !== [];
    }

    /**
     * @return list<int|string>
     */
    public function getPaginationPageOptions(): array
    {
        $options = $this->evaluate($this->paginationPageOptions);

        $options = match (true) {
            $options === true => [10, 25, 50],
            is_int($options) => [$options],
            is_array($options) => $options,
            default => [],
        };

        return array_values(array_filter(
            $options,
            fn (mixed $option): bool => (is_int($option) && $option > 0) || $option === self::ALL_RECORDS,
        ));
    }

    public function getDefaultPaginationPageOption(): int|string|null
    {
        $options = $this->getPaginationPageOptions();
        $default = $this->evaluate($this->defaultPaginationPageOption);

        return in_array($default, $options, true) ? $default : ($options[0] ?? null);
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

    public function serverSide(bool|Closure $condition = true): static
    {
        $this->isServerSide = $condition;

        return $this;
    }

    public function isServerSide(): bool
    {
        return (bool) $this->evaluate($this->isServerSide);
    }

    public function records(?Closure $callback): static
    {
        $this->getRecordsUsing = $callback;

        return $this;
    }

    public function hasRecordsCallback(): bool
    {
        return $this->getRecordsUsing !== null;
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, recordsPerPage: int|string|null}
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getGridPage(mixed $search = null, mixed $page = 1, mixed $recordsPerPage = null): array
    {
        if (! $this->isServerSide()) {
            return ['rows' => [], 'total' => 0, 'page' => 1, 'recordsPerPage' => null];
        }

        $entryPage = $this->resolvePage(
            search: is_string($search) ? mb_substr(trim($search), 0, self::MAX_SEARCH_LENGTH) : '',
            page: is_numeric($page) ? max(1, (int) $page) : 1,
            recordsPerPage: in_array($recordsPerPage, $this->getPaginationPageOptions(), false)
                ? (is_numeric($recordsPerPage) ? (int) $recordsPerPage : self::ALL_RECORDS)
                : $this->getDefaultPaginationPageOption(),
        );

        return [
            'rows' => app(BuildEntryPayload::class)->rows($this, $entryPage->items),
            'total' => $entryPage->total,
            'page' => $entryPage->page,
            'recordsPerPage' => $entryPage->recordsPerPage,
        ];
    }

    public function resolvePage(string $search, int $page, int|string|null $recordsPerPage): EntryPage
    {
        if ($this->getRecordsUsing !== null) {
            return $this->resolvePageFromCallback($search, $page, $recordsPerPage);
        }

        $items = $this->makeItemSchemas($this->normalizeState($this->getState()));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $searchText = app(BuildEntryPayload::class);

            $items = array_filter(
                $items,
                fn (Schema $item): bool => str_contains($searchText->searchText($this, $item), $needle),
            );
        }

        return $this->slicePage($items, $page, $recordsPerPage);
    }

    /**
     * @param  array<array-key, mixed>  $state
     * @return array<string, Schema>
     */
    public function makeItemSchemas(array $state, int $keyOffset = 0): array
    {
        $template = $this->getChildSchema();

        if ($template === null) {
            return [];
        }

        $schemas = [];
        $index = 0;

        foreach ($state as $itemKey => $itemData) {
            $key = $this->isServerSide() ? (string) ($keyOffset + $index) : (string) $itemKey;
            $index++;

            $container = $template
                ->getClone()
                ->statePath($key)
                ->inlineLabel(false);

            if ($itemData instanceof Model) {
                $container->record($itemData);
            } elseif (is_object($itemData)) {
                $container->constantState($itemData);
            } elseif (is_array($itemData)) {
                $container->constantState(array_combine(array_map(strval(...), array_keys($itemData)), $itemData));
            }

            $schemas[$key] = $container;
        }

        return $schemas;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGridPayload(): array
    {
        if (! $this->isServerSide()) {
            return app(BuildEntryPayload::class)->handle($this, $this->getItems(), total: null, page: 1);
        }

        $entryPage = $this->resolvePage('', 1, $this->getDefaultPaginationPageOption());

        return app(BuildEntryPayload::class)->handle($this, $entryPage->items, total: $entryPage->total, page: $entryPage->page);
    }

    public function toEmbeddedHtml(): string
    {
        $html = view('filament-data-grid::data-grid-entry', [
            'entry' => $this,
            'payload' => $this->getGridPayload(),
            'alpineSrc' => FilamentAsset::getAlpineComponentSrc('data-grid-entry', FilamentDataGridServiceProvider::PACKAGE),
            'config' => Js::from([
                'schemaComponent' => $this->getKey(),
                'translations' => [
                    ...__('filament-data-grid::data-grid.entry'),
                    'pagination' => [
                        'overview_one' => trans_choice('filament::components/pagination.overview', 1, ['first' => ':first', 'last' => ':last', 'total' => ':total']),
                        'overview_many' => trans_choice('filament::components/pagination.overview', 2, ['first' => ':first', 'last' => ':last', 'total' => ':total']),
                        'go_to_page' => trans_choice('filament::components/pagination.actions.go_to_page.label', 2, ['page' => ':page']),
                    ],
                ],
                'icons' => [
                    'expand' => generate_icon_html(Heroicon::ChevronRight)?->toHtml(),
                ],
            ]),
        ])->render();

        return $this->wrapEmbeddedHtml($html);
    }

    private function resolvePageFromCallback(string $search, int $page, int|string|null $recordsPerPage): EntryPage
    {
        $result = $this->evaluate($this->getRecordsUsing, [
            'search' => $search === '' ? null : $search,
            'page' => $page,
            'recordsPerPage' => $recordsPerPage === self::ALL_RECORDS ? null : $recordsPerPage,
        ]);

        if ($result instanceof LengthAwarePaginator) {
            $perPage = $recordsPerPage === self::ALL_RECORDS ? max(1, $result->total()) : max(1, $result->perPage());

            return new EntryPage(
                items: $this->makeItemSchemas($this->normalizeState($result->items()), ($result->currentPage() - 1) * $perPage),
                total: $result->total(),
                page: $result->currentPage(),
                recordsPerPage: $recordsPerPage,
            );
        }

        return $this->slicePage($this->makeItemSchemas($this->normalizeState($result)), $page, $recordsPerPage);
    }

    /**
     * @param  array<string, Schema>  $items
     */
    private function slicePage(array $items, int $page, int|string|null $recordsPerPage): EntryPage
    {
        $total = count($items);

        if (! is_int($recordsPerPage)) {
            return new EntryPage(items: $items, total: $total, page: 1, recordsPerPage: $recordsPerPage);
        }

        $page = min($page, max(1, (int) ceil($total / $recordsPerPage)));

        return new EntryPage(
            items: array_slice($items, ($page - 1) * $recordsPerPage, $recordsPerPage, preserve_keys: true),
            total: $total,
            page: $page,
            recordsPerPage: $recordsPerPage,
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function normalizeState(mixed $state): array
    {
        return match (true) {
            $state instanceof Collection => $state->all(),
            $state instanceof Traversable => iterator_to_array($state),
            is_array($state) => $state,
            default => [],
        };
    }
}
