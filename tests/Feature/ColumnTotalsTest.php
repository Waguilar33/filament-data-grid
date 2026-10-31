<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use Filament\Support\RawJs;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedTotals;
use Waguilar\FilamentDataGrid\Tests\Fixtures\EntryPage;
use Waguilar\FilamentDataGrid\Totals\Total;
use Waguilar\FilamentDataGrid\Totals\TotalFormat;

afterEach(function (): void {
    EntryPage::$configureUsing = null;
});

function mountEntry(DataGridEntry $entry): DataGridEntry
{
    EntryPage::$configureUsing = fn (): array => [$entry];

    $mounted = Livewire::test(EntryPage::class)->instance()->getSchema('infolist')?->getComponent('receivables');

    expect($mounted)->toBeInstanceOf(DataGridEntry::class);

    return $mounted;
}

function totalsEntry(): DataGridEntry
{
    return DataGridEntry::make('receivables')
        ->state([
            ['client' => 'Ana', 'total' => 100.5, 'paid' => '40', 'note' => 'n/a'],
            ['client' => 'Luis', 'total' => 20.25, 'paid' => null, 'note' => 'n/a'],
        ])
        ->schema([
            TextEntry::make('client'),
            TextEntry::make('total'),
            TextEntry::make('paid'),
            TextEntry::make('note'),
        ]);
}

it('sends each row the raw numbers of its totaled columns, counting blanks and text as zero', function (): void {
    $payload = mountEntry(totalsEntry()->totals([Total::make('total'), Total::make('paid'), Total::make('note')])->totalsLabel('Total'))->getGridPayload();

    expect(array_column($payload['rows'], 'totals'))->toBe([
        ['total' => 100.5, 'paid' => 40.0, 'note' => 0.0],
        ['total' => 20.25, 'paid' => 0.0, 'note' => 0.0],
    ])
        ->and($payload['totals']['label'])->toBe('Total')
        ->and(array_keys($payload['totals']['columns']))->toBe(['total', 'paid', 'note']);
});

it('leaves totals out of the payload when none are set', function (): void {
    $payload = mountEntry(totalsEntry())->getGridPayload();

    expect($payload['totals'])->toBeNull()
        ->and($payload['rows'][0]['totals'])->toBe([]);
});

it('describes money in the locale the server formats it with', function (): void {
    $format = Total::make('total')->money('GTQ', 'es_GT')->describe()['number'];

    expect($format)->toMatchArray([
        'minFraction' => 2,
        'maxFraction' => 2,
        'decimal' => '.',
        'group' => ',',
        'positivePrefix' => "Q\u{a0}",
        'negativePrefix' => "-Q\u{a0}",
        'halfEven' => true,
    ]);
});

it('describes numeric() with separators like number_format()', function (): void {
    expect(Total::make('total')->numeric(2, ',', '.')->prefix('Q ')->describe())->toMatchArray([
        'number' => TotalFormat::plain(2, ',', '.'),
        'prefix' => 'Q ',
        'suffix' => '',
        'js' => null,
    ]);
});

it('passes a RawJs formatter through', function (): void {
    expect(Total::make('total')->formatUsing(RawJs::make('(value) => value.toFixed(1)'))->describe()['js'])->toBe('(value) => value.toFixed(1)');
});

it('evaluates closures for the format', function (): void {
    expect(Total::make('total')->money(fn (): string => 'EUR', fn (): string => 'de')->describe()['number'])->toMatchArray([
        'decimal' => ',',
        'group' => '.',
        'positiveSuffix' => "\u{a0}€",
    ]);
});

it('refuses totals on a server-side grid', function (): void {
    $entry = mountEntry(totalsEntry()->totals([Total::make('total')]));

    expect(fn () => $entry->serverSide()->getGridPayload())->toThrow(UnsupportedTotals::class);
});
