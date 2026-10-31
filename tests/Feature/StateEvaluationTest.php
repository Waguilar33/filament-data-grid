<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Tests\Fixtures\EntryPage;
use Waguilar\FilamentDataGrid\Totals\Total;
use Workbench\App\Models\DemoOrder;

afterEach(function (): void {
    EntryPage::$configureUsing = null;
    EntryPage::$record = null;
});

function countedLinesEntry(ArrayObject $calls): DataGridEntry
{
    return DataGridEntry::make('lines')
        ->state(function (DemoOrder $record) use ($calls): Collection {
            $calls->append(true);

            return $record->lines()->orderBy('sort')->get();
        })
        ->searchable()
        ->totals([Total::make('quantity')])
        ->schema([
            TextEntry::make('product'),
            TextEntry::make('quantity')->numeric(),
            TextEntry::make('sort'),
            TextEntry::make('id'),
            TextEntry::make('label')->state(fn (TextEntry $component): mixed => data_get($component->getContainer()->getConstantState(), 'product')),
        ]);
}

it('evaluates a state closure once per render', function (): void {
    $order = DemoOrder::create(['reference' => 'A-1']);
    $order->lines()->create(['product' => 'Tea', 'quantity' => 2, 'sort' => 0]);
    $calls = new ArrayObject;

    EntryPage::$record = $order;
    EntryPage::$configureUsing = fn (): array => [countedLinesEntry($calls)];

    $component = Livewire::test(EntryPage::class)->assertSee('Tea');

    expect($calls)->toHaveCount(1);

    $order->lines()->create(['product' => 'Coffee', 'quantity' => 1, 'sort' => 1]);
    $component->call('$refresh')->assertSee('Coffee');

    expect($calls)->toHaveCount(2);
});

it('evaluates it once for a server-side render and once per page request', function (): void {
    $order = DemoOrder::create(['reference' => 'A-1']);
    $order->lines()->create(['product' => 'Tea', 'quantity' => 2, 'sort' => 0]);
    $calls = new ArrayObject;

    EntryPage::$record = $order;
    EntryPage::$configureUsing = fn (): array => [countedLinesEntry($calls)->totals([])->serverSide()->paginated(5)];

    $component = Livewire::test(EntryPage::class);

    expect($calls)->toHaveCount(1);

    $key = (string) $component->instance()->getSchema('infolist')?->getComponent('lines')?->getKey();

    $calls->exchangeArray([]);
    $component->call('callSchemaComponentMethod', $key, 'getGridPage', [null, 1, 5]);

    expect($calls)->toHaveCount(1);
});

it('reads it again after a record action, to find the row and to render fresh rows', function (): void {
    $order = DemoOrder::create(['reference' => 'A-1']);
    $line = $order->lines()->create(['product' => 'Tea', 'quantity' => 2, 'sort' => 0]);
    $calls = new ArrayObject;

    EntryPage::$record = $order;
    EntryPage::$configureUsing = fn (): array => [countedLinesEntry($calls)->recordActions([Action::make('adjust')->action(fn (): null => null)])];

    $component = Livewire::test(EntryPage::class);
    $calls->exchangeArray([]);
    $component->mountAction(TestAction::make('adjust')->schemaComponent('lines', schema: 'infolist')->arguments(['row' => $line->getKey()]));

    expect($calls)->toHaveCount(2);
});
