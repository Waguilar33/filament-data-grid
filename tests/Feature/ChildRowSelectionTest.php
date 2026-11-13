<?php

declare(strict_types=1);

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\View\ViewException;
use Livewire\Features\SupportTesting\Testable;
use Waguilar\FilamentDataGrid\DataGridCheckboxList;
use Workbench\App\Models\DemoOrder;
use Workbench\App\Models\DemoOrderLine;

/**
 * @return array<int, array{number: string, jobs: array<int, array{product: string, ready: bool}>}>
 */
function ordersWithJobs(): array
{
    return [
        1 => ['number' => 'ORD-1', 'jobs' => [1 => ['product' => 'Tea', 'ready' => true], 2 => ['product' => 'Milk', 'ready' => false]]],
        2 => ['number' => 'ORD-2', 'jobs' => [3 => ['product' => 'Coffee', 'ready' => true]]],
    ];
}

function orderPicker(Testable $component): DataGridCheckboxList
{
    $field = $component->instance()->form->getComponent('jobs');

    expect($field)->toBeInstanceOf(DataGridCheckboxList::class);

    return $field;
}

function mountOrderPicker(?Closure $configure = null, ?Closure $records = null): Testable
{
    return mountLines([], fn (): array => [
        ($configure ?? fn (DataGridCheckboxList $field): DataGridCheckboxList => $field)(
            DataGridCheckboxList::make('jobs')
                ->records($records ?? fn (): array => ordersWithJobs())
                ->selectChildRows()
                ->checkIfRecordIsSelectableUsing(fn (array $record): bool => $record['ready'])
                ->table([TableColumn::make('Order')])
                ->schema([
                    TextEntry::make('number'),
                    RepeatableEntry::make('jobs')->table([TableColumn::make('Product')])->schema([TextEntry::make('product')]),
                ]),
        ),
    ]);
}

it('sends the job checkbox column first and each job\'s key, whether it can be selected, its search text and filter values', function (): void {
    $payload = orderPicker(mountOrderPicker(fn (DataGridCheckboxList $field): DataGridCheckboxList => $field
        ->searchable()
        ->filters([SelectFilter::make('product')->options(['Tea' => 'Tea', 'Milk' => 'Milk', 'Coffee' => 'Coffee'])])))->getGrid()->getGridPayload();

    expect($payload['selection'])->toBe(['disabled' => false, 'children' => true])
        ->and($payload['childColumns'][0])->toMatchArray(['selection' => true, 'headerHidden' => true])
        ->and(array_column($payload['rows'], 'selectable'))->toBe([false, false])
        ->and($payload['rows'][0]['childRecords'])->toBe([
            ['key' => '1', 'selectable' => true, 'search' => 'tea', 'filterValues' => ['product' => 'Tea']],
            ['key' => '2', 'selectable' => false, 'search' => 'milk', 'filterValues' => ['product' => 'Milk']],
        ])
        ->and($payload['rows'][0]['children'][0][0])->toBe(['visible' => true]);
});

it('saves job keys and rejects a job that cannot be selected or a key that is no job', function (string $key, bool $isValid): void {
    $component = mountOrderPicker()->set('data.jobs', ['3', $key])->call('save');

    $isValid ? $component->assertHasNoErrors() : $component->assertHasErrors();
})->with([
    'selectable job' => ['1', true],
    'job that cannot be selected' => ['2', false],
    'unknown key' => ['99', false],
]);

it('keys Eloquent child rows by their model key and loads them once with the parents', function (): void {
    $makeOrders = function (int $count): void {
        foreach (range(1, $count) as $index) {
            $order = DemoOrder::query()->create(['reference' => "R-{$index}"]);
            $order->lines()->createMany([['product' => "P-{$index}a", 'quantity' => 1], ['product' => "P-{$index}b", 'quantity' => 2]]);
        }
    };
    $queries = function (): int {
        $component = mountOrderPicker(
            fn (DataGridCheckboxList $field): DataGridCheckboxList => $field
                ->checkIfRecordIsSelectableUsing(fn (DemoOrderLine $record): bool => true)
                ->schema([
                    TextEntry::make('reference'),
                    RepeatableEntry::make('lines')->table([TableColumn::make('Product')])->schema([TextEntry::make('product')]),
                ]),
            fn () => DemoOrder::query()->with('lines')->orderBy('id')->get(),
        );

        DB::flushQueryLog();
        DB::enableQueryLog();
        orderPicker($component)->getGrid()->getGridPayload();
        orderPicker($component)->getSelectableKeys();

        return count(DB::getQueryLog());
    };

    $makeOrders(2);
    $fewer = $queries();
    $makeOrders(3);

    expect($queries())->toBe($fewer);

    $component = mountOrderPicker(
        fn (DataGridCheckboxList $field): DataGridCheckboxList => $field
            ->checkIfRecordIsSelectableUsing(fn (DemoOrderLine $record): bool => true)
            ->schema([
                TextEntry::make('reference'),
                RepeatableEntry::make('lines')->table([TableColumn::make('Product')])->schema([TextEntry::make('product')]),
            ]),
        fn () => DemoOrder::query()->with('lines')->orderBy('id')->get(),
    );
    $lineKeys = DemoOrderLine::query()->orderBy('id')->pluck('id')->map(strval(...))->all();

    expect(orderPicker($component)->getSelectableKeys())->toBe($lineKeys);

    $component->set('data.jobs', [$lineKeys[0]])->call('save')->assertHasNoErrors();
});

it('refuses two child rows with the same key', function (): void {
    mountOrderPicker(records: fn (): array => [
        1 => ['number' => 'ORD-1', 'jobs' => [1 => ['product' => 'Tea', 'ready' => true]]],
        2 => ['number' => 'ORD-2', 'jobs' => [1 => ['product' => 'Coffee', 'ready' => true]]],
    ]);
})->throws(ViewException::class, 'two child rows with the key [1]');

it('refuses child-row selection without child rows or with row groups', function (Closure $configure, string $message): void {
    expect(fn (): Testable => mountOrderPicker($configure))->toThrow(ViewException::class, $message);
})->with([
    'no child rows' => [fn (DataGridCheckboxList $field): DataGridCheckboxList => $field->schema([TextEntry::make('number')]), 'has no child rows'],
    'row groups' => [fn (DataGridCheckboxList $field): DataGridCheckboxList => $field->defaultGroup(Group::make('number')), 'with defaultGroup()'],
]);
