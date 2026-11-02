<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Testing\TestAction;
use Filament\Support\Icons\Heroicon;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Exceptions\DuplicateRecordActionName;
use Waguilar\FilamentDataGrid\Tests\Fixtures\EntryPage;

afterEach(function (): void {
    EntryPage::$configureUsing = null;
});

function emptyStateEntry(DataGridEntry $entry): DataGridEntry
{
    return $entry
        ->emptyStateIcon(Heroicon::OutlinedClock)
        ->emptyStateHeading('Nothing moved yet')
        ->emptyStateDescription('Movements show up here once the shipment leaves.');
}

it('flags an entry whose rows are empty, in the browser and on the server', function (Closure $source): void {
    $empty = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $source(emptyStateEntry($entry), []))->getGridPayload();
    $full = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $source(emptyStateEntry($entry), manyDeliveries(3)))->getGridPayload();

    expect([$empty['isEmpty'], $empty['hasEmptyState']])->toBe([true, true])
        ->and([$full['isEmpty'], $full['hasEmptyState']])->toBe([false, true]);
})->with([
    'browser' => fn (DataGridEntry $entry, array $rows): DataGridEntry => $entry->state($rows),
    'server state' => fn (DataGridEntry $entry, array $rows): DataGridEntry => $entry->state($rows)->serverSide()->paginated(2),
    'server records' => fn (DataGridEntry $entry, array $rows): DataGridEntry => $entry->serverSide()->paginated(2)->records(fn (): array => $rows),
]);

it('keeps the empty flag off when a server search or filter matches nothing but rows exist', function (): void {
    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => emptyStateEntry($entry)->state(manyDeliveries(3))->serverSide()->searchable()->paginated(2));

    expect($entry->getGridPage('nothing-matches')['total'])->toBe(0)
        ->and($entry->getGridPayload()['isEmpty'])->toBeFalse();
});

it('has no empty state until one is configured', function (): void {
    expect(serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state([]))->getGridPayload()['hasEmptyState'])->toBeFalse()
        ->and(serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state([])->emptyStateIcon(fn (): null => null))->hasEmptyState())->toBeFalse()
        ->and(serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state([])->emptyStateIcon(Heroicon::OutlinedClock))->getEmptyStateHeading())->toBe('No records');
});

it('renders Filament table empty state markup only when the source is empty', function (): void {
    EntryPage::$configureUsing = fn (): array => [emptyStateEntry(DataGridEntry::make('deliveries')->heading('History')->state([]))];

    $html = Livewire::test(EntryPage::class)->html();

    expect($html)->toContain('fi-ta-empty-state-icon-bg')
        ->toContain('<h4 class="fi-ta-empty-state-heading">')
        ->toContain('Nothing moved yet')
        ->toContain('<p class="fi-ta-empty-state-description">')
        ->toContain('fi-icon fi-size-lg');

    EntryPage::$configureUsing = fn (): array => [emptyStateEntry(DataGridEntry::make('deliveries')->state(manyDeliveries(2)))];

    expect(Livewire::test(EntryPage::class)->html())->not->toContain('fi-ta-empty-state');
});

it('runs an empty state action on a server-side entry', function (): void {
    $runs = new ArrayObject;

    EntryPage::$configureUsing = fn (): array => [
        emptyStateEntry(DataGridEntry::make('deliveries'))
            ->serverSide()
            ->records(fn (): array => [])
            ->emptyStateActions([
                Action::make('plan')->label('Plan a delivery')->action(fn () => $runs->append(true)),
                ActionGroup::make([Action::make('import')->action(fn () => $runs->append(true))]),
            ]),
    ];

    Livewire::test(EntryPage::class)
        ->assertSee('Plan a delivery')
        ->callAction(TestAction::make('plan')->schemaComponent('deliveries', schema: 'infolist'))
        ->callAction(TestAction::make('import')->schemaComponent('deliveries', schema: 'infolist'));

    expect($runs)->toHaveCount(2);
});

it('refuses an empty state action named like a row action', function (): void {
    $entry = serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->state([]));

    $entry->recordActions([Action::make('open')])->emptyStateActions([Action::make('open')]);

    expect(fn () => $entry->getDefaultActions())->toThrow(DuplicateRecordActionName::class);
});
