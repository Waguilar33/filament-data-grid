<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGrid;
use Workbench\App\Filament\Pages\DispatchDemo;

const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/**
 * @return list<array<string, mixed>>
 */
function dispatchLines(): array
{
    return [
        ['name' => 'Oil', 'requested' => 5, 'has_codes' => false, 'places' => [['place' => 'North', 'quantity' => 2], ['place' => 'South', 'quantity' => 1]], 'units' => []],
        ['name' => 'Drill', 'requested' => 1, 'has_codes' => true, 'places' => [], 'units' => [['place' => 'East', 'code' => null]]],
    ];
}

function mountDispatch(?Closure $configure = null): Testable
{
    return mountLines(dispatchLines(), function () use ($configure): array {
        $grid = DataGrid::make('lines')->schema([
            Hidden::make('has_codes'),
            TextEntry::make('name'),
            TextEntry::make('requested'),
            TextEntry::make('assigned')->state(fn (Get $get): int => (int) collect($get('places'))->sum('quantity')),
            DataGrid::make('places')
                ->visible(fn (Get $get): bool => ! $get('has_codes'))
                ->schema([
                    TextEntry::make('place'),
                    TextInput::make('quantity')->numeric()->minValue(0)->maxValue(10)->live(),
                ]),
            DataGrid::make('units')
                ->visible(fn (Get $get): bool => (bool) $get('has_codes'))
                ->schema([
                    TextEntry::make('place'),
                    TextInput::make('code')->required(),
                ]),
        ]);

        return [$configure ? $configure($grid) : $grid];
    });
}

/**
 * @param  array<string, mixed>  $row
 */
function assignedText(array $row): string
{
    return trim((string) preg_replace('/\s+/', ' ', strip_tags($row['cells']['assigned']['html'])));
}

it('keeps nested grids out of the columns and cells', function (): void {
    $payload = grid(mountDispatch())->getGridPayload();

    expect(array_column($payload['columns'], 'field'))->toBe(['name', 'requested', 'assigned'])
        ->and(array_keys($payload['rows'][0]['cells']))->toBe(['name', 'requested', 'assigned'])
        ->and($payload['hasChildRows'])->toBeTrue();
});

it('has no child rows without a nested grid', function (): void {
    $payload = grid(mountLines([['name' => 'a']], fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')])]))->getGridPayload();

    expect($payload['hasChildRows'])->toBeFalse()
        ->and($payload['rows'][0]['child'])->toBeNull()
        ->and($payload['rows'][0]['hasChildErrors'])->toBeFalse()
        ->and($payload['rows'][0]['expanded'])->toBeFalse();
});

it('builds the child grid with absolute state paths and component keys', function (): void {
    $component = mountDispatch();
    [$oil] = rowKeys($component);
    $child = grid($component)->getGridPayload()['rows'][0]['child'];
    $place = $child['payload']['rows'][0]['key'];

    expect($child['config'])->toBe([
        'statePath' => "data.lines.{$oil}.places",
        'schemaComponent' => "form.lines.{$oil}.places",
        'actions' => ['editCell' => 'editCell', 'reorder' => 'reorder'],
    ])
        ->and($place)->toMatch('/^'.UUID.'$/')
        ->and($child['payload']['rows'][0]['cells']['quantity']['key'])->toBe("form.lines.{$oil}.places.{$place}.quantity")
        ->and($child['payload']['rows'][0]['state']['quantity'])->toEqual(2)
        ->and(array_column($child['payload']['columns'], 'field'))->toBe(['place', 'quantity']);

    expect($component->get("data.lines.{$oil}.places.{$place}.quantity"))->toEqual(2);
});

it('recalculates the parent row when a child cell changes', function (): void {
    $component = mountDispatch();
    [$oil] = rowKeys($component);
    $place = array_key_first($component->get("data.lines.{$oil}.places"));

    expect(assignedText(grid($component)->getGridPayload()['rows'][0]))->toContain('3');

    $component->set("data.lines.{$oil}.places.{$place}.quantity", 7);

    expect(assignedText(grid($component)->getGridPayload()['rows'][0]))->toContain('8');
});

it('edits a child cell through the child grid edit action', function (): void {
    $component = mountDispatch();
    [$oil] = rowKeys($component);
    $place = (string) array_key_first($component->get("data.lines.{$oil}.places"));

    $component->callAction(
        TestAction::make('editCell')->schemaComponent("lines.{$oil}.places")->arguments(['item' => $place, 'field' => 'quantity']),
        ['quantity' => 4],
    );

    expect($component->get("data.lines.{$oil}.places.{$place}.quantity"))->toEqual(4);
});

it('puts a child validation error on the child cell and flags its parent row', function (): void {
    $component = mountDispatch();
    [, $drill] = rowKeys($component);
    $unit = array_key_first($component->get("data.lines.{$drill}.units"));

    $component->call('save')->assertHasErrors(["data.lines.{$drill}.units.{$unit}.code" => 'required']);

    $rows = grid($component)->getGridPayload()['rows'];

    expect($rows[0]['hasChildErrors'])->toBeFalse()
        ->and($rows[1]['hasChildErrors'])->toBeTrue()
        ->and($rows[1]['child']['payload']['rows'][0]['cells']['code']['error'])->toBe('The code field is required.');
});

it('uses the first visible nested grid of each row', function (): void {
    $component = mountDispatch();
    [$oil, $drill] = rowKeys($component);
    $rows = grid($component)->getGridPayload()['rows'];

    expect($rows[0]['child']['config']['statePath'])->toBe("data.lines.{$oil}.places")
        ->and($rows[1]['child']['config']['statePath'])->toBe("data.lines.{$drill}.units");
});

it('has no child when every nested grid of the row is hidden', function (): void {
    $component = mountLines(dispatchLines(), fn (): array => [
        DataGrid::make('lines')->schema([
            TextEntry::make('name'),
            DataGrid::make('places')->hidden()->schema([TextInput::make('quantity')]),
        ]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect($payload['hasChildRows'])->toBeTrue()
        ->and($payload['rows'][0]['child'])->toBeNull();
});

it('expands rows from their state', function (): void {
    $component = mountDispatch(fn (DataGrid $grid): DataGrid => $grid->expandRowsWhen(fn (array $state): bool => $state['has_codes'] === true));

    expect(array_column(grid($component)->getGridPayload()['rows'], 'expanded'))->toBe([false, true]);
});

it('expands rows with a get scoped to the row', function (): void {
    $component = mountDispatch(fn (DataGrid $grid): DataGrid => $grid->expandRowsWhen(
        fn (Get $get): bool => collect($get('places'))->sum('quantity') < $get('requested'),
    ));

    expect(array_column(grid($component)->getGridPayload()['rows'], 'expanded'))->toBe([true, true]);
});

it('expands every row or none from a boolean', function (bool $condition): void {
    $component = mountDispatch(fn (DataGrid $grid): DataGrid => $grid->expandRowsWhen($condition));

    expect(array_column(grid($component)->getGridPayload()['rows'], 'expanded'))->toBe([$condition, $condition]);
})->with([true, false]);

it('pads the child table by 15 pixels unless told otherwise', function (): void {
    expect(grid(mountDispatch())->getGridPayload()['childTablePadding'])->toBe(15)
        ->and(grid(mountDispatch(fn (DataGrid $grid): DataGrid => $grid->childTablePadding(4)))->getGridPayload()['childTablePadding'])->toBe(4)
        ->and(grid(mountDispatch(fn (DataGrid $grid): DataGrid => $grid->childTablePadding(-3)))->getGridPayload()['childTablePadding'])->toBe(0);
});

it('saves the dispatch demo once every unit has a code', function (): void {
    $component = Livewire::test(DispatchDemo::class)->call('save')->assertHasErrors();

    foreach ($component->get('data.products') as $productKey => $product) {
        foreach ($product['units'] as $unitKey => $unit) {
            $component->set("data.products.{$productKey}.units.{$unitKey}.code", $unit['code'] ?? 'NEW-1');
        }
    }

    $component->call('save')->assertHasNoErrors();

    expect($component->get('savedState.products'))->toHaveCount(6)
        ->and($component->get('savedState.products.2.units'))->toContain(['code' => 'NEW-1']);
});

it('opens the dispatch demo products that are short', function (): void {
    $grid = Livewire::test(DispatchDemo::class)->instance()->form->getComponent('products');

    expect($grid)->toBeInstanceOf(DataGrid::class);

    $rows = $grid->getGridPayload()['rows'];

    expect(array_column($rows, 'expanded'))->toBe([false, true, true, true, false, false])
        ->and(array_map(fn (array $row): string => Str::afterLast($row['child']['config']['statePath'], '.'), $rows))
        ->toBe(['places', 'places', 'units', 'places', 'units', 'places']);
});
