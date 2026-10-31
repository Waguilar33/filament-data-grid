<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use BackedEnum;
use Closure;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Traversable;
use Waguilar\FilamentDataGrid\Entry\BuildEntryPayload;
use Waguilar\FilamentDataGrid\Entry\Concerns\HasRecordActions;
use Waguilar\FilamentDataGrid\Entry\DescribeTableRecord;
use Waguilar\FilamentDataGrid\Entry\EntryFilters;
use Waguilar\FilamentDataGrid\Entry\EntryPage;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedChildTable;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedRecordActions;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

use function Filament\Support\generate_icon_html;

class DataGridEntry extends RepeatableEntry
{
    use HasRecordActions;

    public const ALL_RECORDS = 'all';

    public const RECORD_ACTION_ROW_ARGUMENT = 'row';

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

    protected bool $isTableContent = false;

    protected int|Closure $childTablePadding = 15;

    /**
     * @var array<SchemaComponent|Htmlable>|Closure|null
     */
    protected array|Closure|null $expandedAbove = null;

    /**
     * @var array<SchemaComponent|Htmlable>|Closure|null
     */
    protected array|Closure|null $expandedBelow = null;

    protected int|Closure|null $rowHeight = null;

    protected string|Closure|null $childTable = null;

    /**
     * @var array<string, mixed>|Closure
     */
    protected array|Closure $childTableData = [];

    /**
     * @var array<string, array{actions: string|null, link: string|null}>
     */
    protected array $tableRecords = [];

    protected string|Htmlable|Closure|null $heading = null;

    protected string|Htmlable|Closure|null $description = null;

    protected string|BackedEnum|Htmlable|Closure|null $icon = null;

    protected ?Closure $getRecordsUsing = null;

    /**
     * @var list<BaseFilter>|Closure
     */
    protected array|Closure $filters = [];

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

    public function serverSide(bool|Closure $condition = true): static
    {
        $this->isServerSide = $condition;

        return $this;
    }

    public function isServerSide(): bool
    {
        return (bool) $this->evaluate($this->isServerSide);
    }

    /**
     * @param  list<BaseFilter>|Closure  $filters
     */
    public function filters(array|Closure $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return list<BaseFilter>
     */
    public function getFilters(): array
    {
        $filters = $this->evaluate($this->filters);

        return is_array($filters) ? array_values(array_filter($filters, fn (mixed $filter): bool => $filter instanceof BaseFilter)) : [];
    }

    public function getEntryFilters(): EntryFilters
    {
        $filters = new EntryFilters($this->getFilters());

        if ($this->getRecordsUsing === null) {
            $filters->assertSupportedWithoutRecords();
        }

        return $filters;
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
    public function getGridPage(mixed $search = null, mixed $page = 1, mixed $recordsPerPage = null, mixed $filters = []): array
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
            filters: $this->getEntryFilters()->sanitize($filters),
        );

        return [
            'rows' => app(BuildEntryPayload::class)->rows($this, $entryPage->items),
            'total' => $entryPage->total,
            'page' => $entryPage->page,
            'recordsPerPage' => $entryPage->recordsPerPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function resolvePage(string $search, int $page, int|string|null $recordsPerPage, array $filters = []): EntryPage
    {
        if ($this->getRecordsUsing !== null) {
            return $this->resolvePageFromCallback($search, $page, $recordsPerPage, $filters);
        }

        $entryFilters = $this->getEntryFilters();
        $items = array_filter(
            $this->makeItemSchemas($this->normalizeState($this->getState())),
            fn (Schema $item): bool => $entryFilters->matches($item, $filters),
        );

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

    public function childTablePadding(int|Closure $pixels): static
    {
        $this->childTablePadding = $pixels;

        return $this;
    }

    public function getChildTablePadding(): int
    {
        $pixels = $this->evaluate($this->childTablePadding);

        return is_int($pixels) ? max(0, $pixels) : 15;
    }

    public function rowHeight(int|Closure|null $height): static
    {
        $this->rowHeight = $height;

        return $this;
    }

    /**
     * @param  array<SchemaComponent|Htmlable>|Closure|null  $components
     */
    public function expandedAbove(array|Closure|null $components): static
    {
        $this->expandedAbove = $components;

        return $this;
    }

    /**
     * @param  array<SchemaComponent|Htmlable>|Closure|null  $components
     */
    public function expandedBelow(array|Closure|null $components): static
    {
        $this->expandedBelow = $components;

        return $this;
    }

    public function renderExpandedAbove(Schema $item): ?string
    {
        return $this->renderExpandedSlot($this->expandedAbove, $item);
    }

    public function renderExpandedBelow(Schema $item): ?string
    {
        return $this->renderExpandedSlot($this->expandedBelow, $item);
    }

    /**
     * @param  array<SchemaComponent|Htmlable>|Closure|null  $slot
     */
    private function renderExpandedSlot(array|Closure|null $slot, Schema $item): ?string
    {
        if ($slot === null) {
            return null;
        }

        $record = $item->getRecord(withParentComponentRecord: false);
        $state = $record instanceof Model ? $record->toArray() : $item->getConstantState();
        $components = $this->evaluate($slot, [
            'record' => $record ?? $state,
            'state' => $state,
        ], [
            Model::class => $record,
        ]);

        if (! is_array($components) || $components === []) {
            return null;
        }

        $components = array_values(array_map(
            fn (SchemaComponent|Htmlable $component): SchemaComponent|Htmlable => $component instanceof SchemaComponent ? $component->getClone() : $component,
            array_filter($components, fn (mixed $component): bool => $component instanceof SchemaComponent || $component instanceof Htmlable),
        ));

        $html = trim($item->getClone()->components($components)->toHtml());

        return $html === '' ? null : $html;
    }

    /**
     * @param  class-string<Component>|Closure|null  $component
     * @param  array<string, mixed>|Closure  $data
     */
    public function childTable(string|Closure|null $component, array|Closure $data = []): static
    {
        $this->childTable = $component;
        $this->childTableData = $data;

        return $this;
    }

    public function getChildTableComponent(): ?string
    {
        $component = $this->evaluate($this->childTable);

        return is_string($component) && $component !== '' ? $component : null;
    }

    public function hasChildTable(): bool
    {
        return $this->getChildTableComponent() !== null;
    }

    /**
     * @param  array<array-key, Schema>  $items
     * @return array<string, string>
     */
    public function renderChildTables(array $items): array
    {
        $component = $this->getChildTableComponent();

        if ($component === null) {
            return [];
        }

        $html = [];

        foreach ($items as $key => $item) {
            $html[(string) $key] = $this->renderChildTable($component, (string) $key, $item);
        }

        return $html;
    }

    private function renderChildTable(string $component, string $key, Schema $item): string
    {
        $record = $item->getRecord(withParentComponentRecord: false);
        $state = $record instanceof Model ? $record->toArray() : $item->getConstantState();
        $data = $this->evaluate($this->childTableData, [
            'record' => $record ?? $state,
            'state' => $state,
        ], [
            Model::class => $record,
        ]);

        $child = $item->getClone()
            ->components([
                LivewireComponent::make($component, is_array($data) ? array_filter($data, is_string(...), ARRAY_FILTER_USE_KEY) : [])
                    ->key('child-table')
                    ->lazy(),
            ])
            ->getComponents()[0] ?? null;

        return $child instanceof LivewireComponent ? $child->toEmbeddedHtml() : '';
    }

    public function hasRowHeightPerRow(): bool
    {
        return $this->rowHeight instanceof Closure;
    }

    /**
     * @param  array<array-key, mixed>  $state
     */
    public function getRowHeight(array $state = []): ?int
    {
        $height = $this->evaluate($this->rowHeight, ['state' => $state]);

        return is_int($height) && $height > 0 ? $height : null;
    }

    public function isTableContent(): bool
    {
        return $this->isTableContent;
    }

    public function toTableContent(): View
    {
        $this->isTableContent = true;

        return view('filament-data-grid::table-content', ['entry' => $this]);
    }

    /**
     * @param  iterable<array-key, mixed>  $records
     */
    public function renderTableRecords(HasSchemas&HasTable&Component $livewire, iterable $records): Htmlable
    {
        $state = [];
        $this->tableRecords = [];
        $table = $livewire->getTable();
        $describeRecord = app(DescribeTableRecord::class);

        foreach ($records instanceof Paginator ? $records->items() : $records as $key => $record) {
            $recordKey = (string) ($record instanceof Model ? $livewire->getTableRecordKey($record) : $key);
            $state[$recordKey] = $record;

            if ($record instanceof Model) {
                $this->tableRecords[$recordKey] = $describeRecord->handle($table, $record);
            } elseif (is_array($record)) {
                $this->tableRecords[$recordKey] = $describeRecord->handle($table, array_combine(array_map(strval(...), array_keys($record)), $record));
            }
        }

        $this->state($state)->hiddenLabel();

        return Schema::make($livewire)->components([$this]);
    }

    /**
     * @return array{actions: string|null, link: string|null}
     */
    public function describeRecord(string $key, Schema $item): array
    {
        return $this->tableRecords[$key] ?? ['actions' => $this->renderRecordActions($key, $item), 'link' => null];
    }

    /**
     * @return array<string, mixed>
     */
    public function getGridPayload(): array
    {
        return $this->buildGridPayload($this->isServerSide() ? null : $this->getItems());
    }

    /**
     * @param  array<array-key, Schema>|null  $items
     * @return array<string, mixed>
     */
    private function buildGridPayload(?array $items): array
    {
        if ($this->isServerSide() && $this->hasChildTable()) {
            throw UnsupportedChildTable::serverSide((string) $this->getName());
        }

        if ($this->hasRecordActions() && ($this->isServerSide() || $this->isTableContent)) {
            throw $this->isServerSide()
                ? UnsupportedRecordActions::serverSide((string) $this->getName())
                : UnsupportedRecordActions::tableContent((string) $this->getName());
        }

        if ($items !== null) {
            return app(BuildEntryPayload::class)->handle($this, $items, total: null, page: 1);
        }

        $entryPage = $this->resolvePage('', 1, $this->getDefaultPaginationPageOption(), $this->getEntryFilters()->sanitize([]));

        return app(BuildEntryPayload::class)->handle($this, $entryPage->items, total: $entryPage->total, page: $entryPage->page);
    }

    public function toEmbeddedHtml(): string
    {
        $items = $this->isServerSide() ? null : $this->getItems();

        $html = view($this->isTableContent() ? 'filament-data-grid::data-grid-table-content' : 'filament-data-grid::data-grid-entry', [
            'entry' => $this,
            'payload' => $this->buildGridPayload($items),
            'childTables' => $items === null ? [] : $this->renderChildTables($items),
            'alpineSrc' => FilamentAsset::getAlpineComponentSrc('data-grid-entry', FilamentDataGridServiceProvider::PACKAGE),
            'config' => Js::from([
                'schemaComponent' => $this->getKey(),
                'translations' => [
                    ...__('filament-data-grid::data-grid.entry'),
                    'actions' => __('filament-data-grid::data-grid.columns.actions'),
                    'pagination' => [
                        'overview_one' => trans_choice('filament::components/pagination.overview', 1, ['first' => ':first', 'last' => ':last', 'total' => ':total']),
                        'overview_many' => trans_choice('filament::components/pagination.overview', 2, ['first' => ':first', 'last' => ':last', 'total' => ':total']),
                        'go_to_page' => trans_choice('filament::components/pagination.actions.go_to_page.label', 2, ['page' => ':page']),
                    ],
                ],
                'icons' => [
                    'expand' => generate_icon_html(Heroicon::ChevronRight, DataGridIconAlias::ROW_EXPAND)?->toHtml(),
                ],
            ]),
        ])->render();

        return $this->wrapEmbeddedHtml($html);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolvePageFromCallback(string $search, int $page, int|string|null $recordsPerPage, array $filters): EntryPage
    {
        $result = $this->evaluate($this->getRecordsUsing, [
            'filters' => $this->getEntryFilters()->toFilamentData($filters),
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
