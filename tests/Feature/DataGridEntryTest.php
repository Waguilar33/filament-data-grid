<?php

declare(strict_types=1);

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
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
        ->assertSeeHtml('fi-data-grid-search')
        ->assertSeeHtml('fi-data-grid-pagination');
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
