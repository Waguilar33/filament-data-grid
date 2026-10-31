<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedRecordActions;
use Waguilar\FilamentDataGrid\Tests\Fixtures\EntryPage;
use Workbench\App\Models\DemoOrder;
use Workbench\App\Models\DemoOrderLine;

afterEach(function (): void {
    EntryPage::$configureUsing = null;
    EntryPage::$record = null;
});

function adjustAction(): Action
{
    return Action::make('adjust')
        ->visible(fn (DemoOrderLine $record): bool => (float) $record->quantity < 10)
        ->fillForm(fn (DemoOrderLine $record): array => ['quantity' => $record->quantity])
        ->schema(fn (DemoOrderLine $record): array => [TextInput::make('quantity')->numeric()->maxValue(100)->helperText($record->product)])
        ->action(fn (array $data, DemoOrderLine $record) => $record->update(['quantity' => $data['quantity']]));
}

/**
 * @param  Closure(DataGridEntry): DataGridEntry|null  $configure
 */
function mountOrderLines(DemoOrder $order, ?Closure $configure = null): Testable
{
    EntryPage::$record = $order;
    EntryPage::$configureUsing = fn (): array => [
        Section::make('Lines')->schema([
            ($configure ?? fn (DataGridEntry $entry): DataGridEntry => $entry)(
                DataGridEntry::make('lines')
                    ->state(fn (DemoOrder $record) => $record->lines()->reorder()->latest('id')->get())
                    ->table([TableColumn::make('Product'), TableColumn::make('Quantity')])
                    ->schema([TextEntry::make('product'), TextEntry::make('quantity')])
                    ->recordActions([adjustAction()]),
            ),
        ]),
    ];

    return Livewire::test(EntryPage::class);
}

function orderWithLines(): DemoOrder
{
    $order = DemoOrder::create(['reference' => 'A-1']);
    $order->lines()->create(['product' => 'Tea', 'quantity' => 2]);
    $order->lines()->create(['product' => 'Coffee', 'quantity' => 40]);
    $order->lines()->create(['product' => 'Salt', 'quantity' => 5]);

    return $order;
}

/**
 * @return array<string, string|null>
 */
function rowActions(Testable $component, string $name = 'lines'): array
{
    $entry = $component->instance()->getSchema('infolist')?->getComponent($name);

    expect($entry)->toBeInstanceOf(DataGridEntry::class);

    return array_column($entry->getGridPayload()['rows'], 'actions', 'key');
}

function rowArgument(mixed $key): string
{
    return '\\u0022row\\u0022:\\u0022'.$key.'\\u0022';
}

function adjustOn(string|int $row): TestAction
{
    return TestAction::make('adjust')->schemaComponent('lines', schema: 'infolist')->arguments(['row' => $row]);
}

it('renders each row its own record actions, as a table does', function (): void {
    $order = orderWithLines();
    [$tea, $coffee, $salt] = $order->lines()->orderBy('id')->get()->all();

    $actions = array_values(rowActions(mountOrderLines($order)));

    expect($actions[0])->toContain('fi-data-grid-record-actions')
        ->toContain('fi-link')
        ->toContain(rowArgument($salt->getKey()))
        ->and($actions[1])->toBeNull()
        ->and($actions[2])->toContain(rowArgument($tea->getKey()))
        ->and($coffee->getKey())->not->toBeNull();
});

it('calls the action for the row it was clicked on, with that row as the record', function (): void {
    $order = orderWithLines();
    $tea = $order->lines()->where('product', 'Tea')->sole();

    mountOrderLines($order)
        ->assertActionVisible(adjustOn($tea->getKey()))
        ->assertActionHidden(adjustOn($order->lines()->where('product', 'Coffee')->sole()->getKey()))
        ->mountAction(adjustOn($tea->getKey()))
        ->assertSchemaStateSet(['quantity' => '2.000'], 'mountedActionSchema0')
        ->fillForm(['quantity' => 7])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect((float) $tea->refresh()->quantity)->toBe(7.0)
        ->and($order->lines()->where('product', '!=', 'Tea')->pluck('quantity')->map(fn (string $quantity): float => (float) $quantity)->all())->toBe([40.0, 5.0]);
});

it('validates the action form', function (): void {
    $order = orderWithLines();

    mountOrderLines($order)
        ->callAction(adjustOn($order->lines()->where('product', 'Tea')->sole()->getKey()), ['quantity' => 500])
        ->assertHasActionErrors(['quantity' => 'max']);
});

it('finds the row by its record key, so new rows above it do not shift it', function (): void {
    $order = orderWithLines();
    $tea = $order->lines()->where('product', 'Tea')->sole();
    $component = mountOrderLines($order);

    $order->lines()->create(['product' => 'Sugar', 'quantity' => 1]);

    $component->callAction(adjustOn($tea->getKey()), ['quantity' => 3]);

    expect((float) $tea->refresh()->quantity)->toBe(3.0)
        ->and((float) $order->lines()->where('product', 'Sugar')->sole()->quantity)->toBe(1.0);
});

it('runs nothing when the row is gone, and never falls back to the page record', function (): void {
    $order = orderWithLines();
    $salt = $order->lines()->where('product', 'Salt')->sole();
    $component = mountOrderLines($order);

    $salt->delete();

    $component->call('mountAction', 'adjust', ['row' => $salt->getKey()], ['schemaComponent' => 'infolist.lines'])
        ->assertSet('mountedActions', []);

    expect($order->lines()->orderBy('id')->pluck('quantity')->map(fn (string $quantity): float => (float) $quantity)->all())->toBe([2.0, 40.0])
        ->and($order->refresh()->reference)->toBe('A-1');
});

it('hides the action in the same response once the row no longer qualifies', function (): void {
    $order = orderWithLines();
    $tea = $order->lines()->where('product', 'Tea')->sole();

    $component = mountOrderLines($order)->callAction(adjustOn($tea->getKey()), ['quantity' => 50]);

    expect(array_values(rowActions($component))[2])->toBeNull()
        ->and($component->html())->not->toContain(e(rowArgument($tea->getKey())));
});

it('gives array rows their own state as the record, keyed by the array key', function (): void {
    $seen = [];
    EntryPage::$record = DemoOrder::create(['reference' => 'A-1']);
    EntryPage::$configureUsing = function () use (&$seen): array {
        return [
            DataGridEntry::make('lines')
                ->state(['tea' => ['product' => 'Tea'], 'salt' => ['product' => 'Salt']])
                ->schema([TextEntry::make('product')])
                ->recordActions([
                    Action::make('adjust')->action(function (array $record) use (&$seen): void {
                        $seen[] = $record;
                    }),
                ]),
        ];
    };

    Livewire::test(EntryPage::class)->callAction(adjustOn('salt'));

    expect($seen)->toBe([['product' => 'Salt', '__key' => 'salt']]);
});

it('shows grouped record actions as a menu', function (): void {
    $order = orderWithLines();
    $tea = $order->lines()->where('product', 'Tea')->sole();

    $component = mountOrderLines($order, fn (DataGridEntry $entry): DataGridEntry => $entry->recordActions([
        ActionGroup::make([adjustAction(), Action::make('remove')->action(fn (DemoOrderLine $record) => $record->delete())]),
    ]));

    expect(array_values(rowActions($component))[2])->toContain('fi-dropdown')
        ->toContain("mountAction('remove'");

    $component->callAction(TestAction::make('remove')->schemaComponent('lines', schema: 'infolist')->arguments(['row' => $tea->getKey()]));

    expect(DemoOrderLine::query()->whereKey($tea->getKey())->exists())->toBeFalse()
        ->and(DemoOrderLine::query()->count())->toBe(2);
});

it('refuses record actions with server-side pages or inside a table', function (): void {
    $entry = DataGridEntry::make('lines')
        ->serverSide()
        ->records(fn (): array => [])
        ->recordActions([Action::make('adjust')])
        ->schema([TextEntry::make('product')]);

    expect(fn () => $entry->getGridPayload())->toThrow(UnsupportedRecordActions::class);

    $entry->serverSide(false)->state([])->toTableContent();

    expect(fn () => $entry->getGridPayload())->toThrow(UnsupportedRecordActions::class);
});

it('authorizes each row on its own record', function (): void {
    $order = orderWithLines();
    $salt = $order->lines()->where('product', 'Salt')->sole();

    $component = mountOrderLines($order, fn (DataGridEntry $entry): DataGridEntry => $entry->recordActions([
        adjustAction()->authorize(fn (DemoOrderLine $record): bool => $record->product !== 'Salt'),
    ]));

    expect(array_values(rowActions($component)))->sequence(
        fn ($actions) => $actions->toBeNull(),
        fn ($actions) => $actions->toBeNull(),
        fn ($actions) => $actions->toContain(rowArgument($order->lines()->where('product', 'Tea')->sole()->getKey())),
    );

    $component->call('mountAction', 'adjust', ['row' => $salt->getKey()], ['schemaComponent' => 'infolist.lines'])
        ->assertSet('mountedActions', []);
});

it('uploads a file from the action modal for its row', function (): void {
    Storage::fake('local');
    $order = orderWithLines();
    $tea = $order->lines()->where('product', 'Tea')->sole();
    mountOrderLines($order, fn (DataGridEntry $entry): DataGridEntry => $entry->recordActions([
        Action::make('attach')
            ->schema([FileUpload::make('receipt')->disk('local')->directory('receipts')->required()])
            ->action(fn (array $data, DemoOrderLine $record): bool => $record->update(['product' => $data['receipt']])),
    ]))
        ->mountAction(TestAction::make('attach')->schemaComponent('lines', schema: 'infolist')->arguments(['row' => $tea->getKey()]))
        ->fillForm(['receipt' => UploadedFile::fake()->image('receipt.png')])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $receipt = $tea->refresh()->product;

    expect($receipt)->toStartWith('receipts/')
        ->and($order->lines()->whereIn('product', ['Coffee', 'Salt'])->count())->toBe(2);

    Storage::disk('local')->assertExists($receipt);
});
