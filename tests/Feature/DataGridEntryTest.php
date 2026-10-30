<?php

declare(strict_types=1);

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Entry\EntryFilters;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedFilter;
use Waguilar\FilamentDataGrid\Tests\Fixtures\EntryPage;

/**
 * @param  Closure(DataGridEntry): DataGridEntry|null  $configure
 */
function mountDeliveries(?Closure $configure = null): Testable
{
    EntryPage::$configureUsing = fn (): array => [
        ($configure ?? fn (DataGridEntry $entry): DataGridEntry => $entry)(
            DataGridEntry::make('deliveries')
                ->state([
                    ['number' => 'DLV-1', 'customer' => 'Ana Torres', 'status' => 'complete', 'lines' => [
                        ['product' => 'Tea', 'requested' => 2, 'handed_over' => 2],
                        ['product' => 'Coffee', 'requested' => 1500, 'handed_over' => 1499],
                    ]],
                    ['number' => 'DLV-2', 'customer' => 'Bruno Díaz', 'status' => 'partial', 'lines' => []],
                ])
                ->table([
                    TableColumn::make('Delivery'),
                    TableColumn::make('Customer'),
                    TableColumn::make('Status')->width('10rem')->alignCenter(),
                ])
                ->schema([
                    TextEntry::make('number'),
                    TextEntry::make('customer'),
                    TextEntry::make('status')->badge(),
                    RepeatableEntry::make('lines')
                        ->table([
                            TableColumn::make('Product'),
                            TableColumn::make('Requested')->alignEnd(),
                            TableColumn::make('Handed over')->alignEnd(),
                        ])
                        ->schema([
                            TextEntry::make('product'),
                            TextEntry::make('requested')->numeric(),
                            TextEntry::make('handed_over')->numeric(),
                        ]),
                ]),
        ),
    ];

    return Livewire::test(EntryPage::class);
}

/**
 * @return array<string, mixed>
 */
function deliveriesPayload(Testable $component): array
{
    $entry = $component->instance()->getSchema('infolist')?->getComponent('deliveries');

    expect($entry)->toBeInstanceOf(DataGridEntry::class);

    return $entry->getGridPayload();
}

afterEach(function (): void {
    EntryPage::$configureUsing = null;
});

it('turns the entries into columns and the table repeatable entry into child rows', function (): void {
    $payload = deliveriesPayload(mountDeliveries());

    expect(array_column($payload['columns'], 'field'))->toBe(['number', 'customer', 'status'])
        ->and($payload['columns'][2])->toMatchArray(['label' => 'Status', 'width' => '10rem', 'alignment' => 'center'])
        ->and(array_column($payload['childColumns'], 'label'))->toBe(['Product', 'Requested', 'Handed over'])
        ->and($payload['childColumns'][1]['alignment'])->toBe('end');
});

it('renders each parent and child cell with Filament entry markup', function (): void {
    $payload = deliveriesPayload(mountDeliveries());
    [$first, $second] = $payload['rows'];

    expect($first['cells']['status']['html'])->toContain('fi-badge')->toContain('complete')
        ->and($first['children'])->toHaveCount(2)
        ->and($first['children'][1][0]['html'])->toContain('Coffee')
        ->and($first['children'][1][1]['html'])->toContain('1,500')
        ->and($second['children'])->toBe([]);
});

it('builds a lowercase search text from the searchable columns only', function (): void {
    $payload = deliveriesPayload(mountDeliveries(fn (DataGridEntry $entry): DataGridEntry => $entry->searchable(['number', 'customer'])));

    expect($payload['rows'][1]['search'])->toBe('dlv-2 bruno díaz');
});

it('searches every column when searchable has no field list', function (): void {
    $payload = deliveriesPayload(mountDeliveries(fn (DataGridEntry $entry): DataGridEntry => $entry->searchable()));

    expect($payload['rows'][0]['search'])->toBe('dlv-1 ana torres complete');
});

it('sends no search text and no page size by default', function (): void {
    $payload = deliveriesPayload(mountDeliveries());

    expect($payload['rows'][0]['search'])->toBe('')
        ->and($payload['recordsPerPage'])->toBeNull();
});

it('pages parents by the configured size', function (): void {
    $payload = deliveriesPayload(mountDeliveries(fn (DataGridEntry $entry): DataGridEntry => $entry->paginated(25)));

    expect($payload['recordsPerPage'])->toBe(25);
});

it('has no child rows without a table repeatable entry', function (): void {
    EntryPage::$configureUsing = fn (): array => [
        DataGridEntry::make('deliveries')
            ->state([['number' => 'DLV-1']])
            ->schema([TextEntry::make('number')]),
    ];

    $payload = deliveriesPayload(Livewire::test(EntryPage::class));

    expect($payload['childColumns'])->toBe([])
        ->and($payload['rows'][0]['children'])->toBe([]);
});

it('renders the grid component with its search and pagination controls', function (): void {
    mountDeliveries(fn (DataGridEntry $entry): DataGridEntry => $entry->searchable()->paginated(10))
        ->assertSeeHtml('filamentDataGridEntry(')
        ->assertSeeHtml('fi-ta-search-field')
        ->assertSeeHtml('fi-pagination');
});

it('searches the text users see, as formatted by the entry', function (): void {
    EntryPage::$configureUsing = fn (): array => [
        DataGridEntry::make('deliveries')
            ->state([['status' => 'complete']])
            ->searchable()
            ->schema([TextEntry::make('status')->formatStateUsing(fn (string $state): string => "Fully {$state}")]),
    ];

    $payload = deliveriesPayload(Livewire::test(EntryPage::class));

    expect($payload['rows'][0]['search'])->toBe('fully complete');
});

/**
 * @return list<array{number: string, customer: string}>
 */
function manyDeliveries(int $count): array
{
    return array_map(
        fn (int $index): array => ['number' => sprintf('DLV-%02d', $index), 'customer' => $index % 2 === 0 ? 'Ana' : 'Bruno'],
        range(1, $count),
    );
}

function serverEntry(Closure $configure): DataGridEntry
{
    EntryPage::$configureUsing = fn (): array => [
        $configure(DataGridEntry::make('deliveries')->schema([TextEntry::make('number'), TextEntry::make('customer')])),
    ];

    $entry = Livewire::test(EntryPage::class)->instance()->getSchema('infolist')?->getComponent('deliveries');

    expect($entry)->toBeInstanceOf(DataGridEntry::class);

    return $entry;
}

it('pages in the browser unless told otherwise', function (): void {
    $payload = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(manyDeliveries(12))->paginated(5))->getGridPayload();

    expect($payload['serverSide'])->toBeFalse()
        ->and($payload['rows'])->toHaveCount(12);
});

it('lets the app make every entry server-side and a single entry opt out', function (): void {
    DataGridEntry::configureUsing(fn (DataGridEntry $entry): DataGridEntry => $entry->serverSide());

    try {
        expect(serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry)->isServerSide())->toBeTrue()
            ->and(serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->serverSide(false))->isServerSide())->toBeFalse();
    } finally {
        DataGridEntry::configureUsing(fn (DataGridEntry $entry): DataGridEntry => $entry->serverSide(false));
    }
});

it('sends only the first page on load when server-side', function (): void {
    $payload = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(manyDeliveries(12))->serverSide()->paginated(5))->getGridPayload();

    expect($payload['serverSide'])->toBeTrue()
        ->and($payload['total'])->toBe(12)
        ->and(array_column($payload['rows'], 'key'))->toBe(['0', '1', '2', '3', '4']);
});

it('searches and pages the state on the server', function (): void {
    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(manyDeliveries(12))->serverSide()->searchable()->paginated([2, 5]));

    $result = $entry->getGridPage('ana', 2, 5);

    expect($result['total'])->toBe(6)
        ->and($result['page'])->toBe(2)
        ->and($result['recordsPerPage'])->toBe(5)
        ->and($result['rows'])->toHaveCount(1)
        ->and($result['rows'][0]['cells']['number']['html'])->toContain('DLV-12');
});

it('clamps the page and ignores page sizes it does not offer', function (): void {
    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(manyDeliveries(12))->serverSide()->paginated([5]));

    $result = $entry->getGridPage(null, 99, 1000);

    expect($result['page'])->toBe(3)
        ->and($result['recordsPerPage'])->toBe(5)
        ->and($result['rows'])->toHaveCount(2);
});

it('answers junk arguments with the first page', function (): void {
    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(manyDeliveries(12))->serverSide()->paginated(5));

    $result = $entry->getGridPage(['not' => 'text'], 'abc', ['x']);

    expect($result['page'])->toBe(1)
        ->and($result['rows'])->toHaveCount(5);
});

it('passes the search, page and page size to records() and uses its total', function (): void {
    $received = new ArrayObject;

    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry
        ->serverSide()
        ->paginated([5, DataGridEntry::ALL_RECORDS])
        ->records(function (?string $search, int $page, ?int $recordsPerPage) use ($received): LengthAwarePaginator {
            $received[] = [$search, $page, $recordsPerPage];

            return new LengthAwarePaginator([['number' => 'DLV-99', 'customer' => 'Ana']], 40, 5, $page);
        }));

    $result = $entry->getGridPage('ana', 3, 5);
    $entry->getGridPage(null, 1, 'all');

    expect($received[1] ?? null)->toBe(['ana', 3, 5])
        ->and($received[2] ?? null)->toBe([null, 1, null])
        ->and($result['total'])->toBe(40)
        ->and($result['page'])->toBe(3)
        ->and($result['rows'][0]['key'])->toBe('10');
});

it('returns nothing to the browser when the entry is not server-side', function (): void {
    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(manyDeliveries(3))->paginated(5));

    expect($entry->getGridPage(null, 1, 5)['rows'])->toBe([]);
});

it('only lets the browser call the page method', function (): void {
    EntryPage::$configureUsing = fn (): array => [
        DataGridEntry::make('deliveries')->state(manyDeliveries(3))->serverSide()->paginated(5)->schema([TextEntry::make('number')]),
    ];

    $page = Livewire::test(EntryPage::class)->instance();
    $key = $page->getSchema('infolist')?->getComponent('deliveries')?->getKey();

    expect($page->callSchemaComponentMethod((string) $key, 'getGridPayload'))->toBeNull()
        ->and($page->callSchemaComponentMethod((string) $key, 'getGridPage', [null, 1, 5]))->toHaveKey('total', 3);
});

/**
 * @return list<array{number: string, customer: string, status: string, late: bool}>
 */
function filterableDeliveries(): array
{
    return [
        ['number' => 'DLV-1', 'customer' => 'Ana', 'status' => 'complete', 'late' => false],
        ['number' => 'DLV-2', 'customer' => 'Bruno', 'status' => 'partial', 'late' => true],
        ['number' => 'DLV-3', 'customer' => 'Carla', 'status' => 'partial', 'late' => false],
    ];
}

function filterableEntry(Closure $configure): DataGridEntry
{
    return serverEntry(fn (DataGridEntry $entry): DataGridEntry => $configure($entry
        ->filters([
            SelectFilter::make('status')->options(['complete' => 'Complete', 'partial' => 'Partial']),
            SelectFilter::make('customer')->multiple()->options(['Ana' => 'Ana', 'Bruno' => 'Bruno', 'Carla' => 'Carla']),
            TernaryFilter::make('late'),
        ])
        ->schema([TextEntry::make('number'), TextEntry::make('customer')])));
}

it('describes Filament filters and sends each row its raw filter values', function (): void {
    $payload = filterableEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(filterableDeliveries()))->getGridPayload();

    expect(array_column($payload['filters'], 'type', 'name'))->toBe(['status' => 'select', 'customer' => 'select', 'late' => 'ternary'])
        ->and($payload['filters'][1]['multiple'])->toBeTrue()
        ->and($payload['filters'][0]['options'][1])->toBe(['value' => 'partial', 'label' => 'Partial'])
        ->and($payload['rows'][1]['filterValues'])->toBe(['status' => 'partial', 'customer' => 'Bruno', 'late' => true]);
});

it('applies select, multiple select and ternary filters to the state on the server', function (): void {
    $entry = filterableEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state(filterableDeliveries())->serverSide()->paginated(10));

    $numbers = fn (array $filters): array => array_map(
        fn (array $row): string => strip_tags((string) preg_replace('/.*?(DLV-\d).*/s', '$1', $row['cells']['number']['html'])),
        $entry->getGridPage(null, 1, 10, $filters)['rows'],
    );

    expect($numbers(['status' => 'partial']))->toBe(['DLV-2', 'DLV-3'])
        ->and($numbers(['customer' => ['Ana', 'Carla']]))->toBe(['DLV-1', 'DLV-3'])
        ->and($numbers(['late' => 'true']))->toBe(['DLV-2'])
        ->and($numbers(['late' => 'false', 'status' => 'partial']))->toBe(['DLV-3'])
        ->and($numbers(['status' => 'unknown', 'customer' => ['Nobody'], 'late' => 'maybe']))->toBe(['DLV-1', 'DLV-2', 'DLV-3']);
});

it('passes the filters to records() in the shape Filament tables use', function (): void {
    $received = new ArrayObject;

    $entry = filterableEntry(fn (DataGridEntry $entry): DataGridEntry => $entry
        ->serverSide()
        ->paginated(10)
        ->records(function (array $filters) use ($received): array {
            $received[] = $filters;

            return filterableDeliveries();
        }));

    $entry->getGridPage(null, 1, 10, ['status' => 'partial', 'customer' => ['Ana'], 'late' => 'false']);

    expect($received[count($received) - 1])->toBe([
        'status' => ['value' => 'partial'],
        'customer' => ['values' => ['Ana']],
        'late' => ['value' => false],
    ]);
});

it('refuses filters it cannot apply without records()', function (): void {
    $entry = DataGridEntry::make('deliveries')
        ->state(filterableDeliveries())
        ->filters([Filter::make('has_code')]);

    expect(fn () => $entry->getEntryFilters())->toThrow(UnsupportedFilter::class)
        ->and($entry->records(fn (): array => [])->getEntryFilters())->toBeInstanceOf(EntryFilters::class);
});

it('renders a heading, description and icon above the grid', function (): void {
    mountDeliveries(fn (DataGridEntry $entry): DataGridEntry => $entry
        ->heading('Deliveries')
        ->description('Where each order stands')
        ->icon(Heroicon::OutlinedTruck))
        ->assertSeeHtml('fi-ta-header-heading')
        ->assertSee('Deliveries')
        ->assertSee('Where each order stands')
        ->assertSeeHtml('fi-data-grid-entry-header-icon');
});
