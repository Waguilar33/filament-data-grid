<?php

declare(strict_types=1);

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGrid;
use Waguilar\FilamentDataGrid\Tests\Fixtures\PaymentsForm;

afterEach(function (): void {
    PaymentsForm::$configureUsing = null;
    PaymentsForm::$initialData = [];
});

/**
 * @return array<string, mixed>
 */
function unitsPayload(DataGrid $grid): array
{
    PaymentsForm::$initialData = ['units' => [
        'desk' => ['name' => 'Desk', 'order' => 'PO-3', 'codes' => []],
        'drill' => ['name' => 'Drill', 'order' => null, 'codes' => [['code' => 'CD-1']]],
    ]];
    PaymentsForm::$configureUsing = fn (): array => [$grid];

    $mounted = Livewire::test(PaymentsForm::class)->instance()->getSchema('form')?->getComponent('units');

    expect($mounted)->toBeInstanceOf(DataGrid::class);

    return $mounted->getGridPayload();
}

function unitsGrid(): DataGrid
{
    return DataGrid::make('units')
        ->addable(false)
        ->schema([
            Hidden::make('order'),
            TextInput::make('name'),
            DataGrid::make('codes')
                ->visible(fn (Get $get): bool => $get('order') === null)
                ->schema([TextInput::make('code')]),
        ]);
}

it('renders content above and below each row from its state and Get', function (): void {
    $payload = unitsPayload(unitsGrid()
        ->expandedAbove(fn (array $state): array => [Text::make("Above {$state['name']}")])
        ->expandedBelow(fn (Get $get): ?array => $get('order') ? [Text::make("Arrives with {$get('order')}")] : null));

    $rows = collect($payload['rows'])->keyBy(fn (array $row): string => mb_strtolower($row['state']['name']));

    expect($rows['desk']['above'])->toContain('Above Desk')
        ->and($rows['desk']['below'])->toContain('Arrives with PO-3')
        ->and($rows['desk']['child'])->toBeNull()
        ->and($rows['drill']['above'])->toContain('Above Drill')
        ->and($rows['drill']['below'])->toBeNull()
        ->and($rows['drill']['child'])->not->toBeNull();
});

it('marks a grid with only content as having child rows', function (): void {
    $payload = unitsPayload(DataGrid::make('units')
        ->addable(false)
        ->schema([TextInput::make('name')])
        ->expandedBelow([Text::make('Static note')]));

    expect($payload['hasChildRows'])->toBeTrue()
        ->and(array_column($payload['rows'], 'below'))->each->toContain('Static note');
});

it('leaves content out of the trailing empty row and grids without slots', function (): void {
    $payload = unitsPayload(unitsGrid()->addable()->trailingEmptyRow()->expandedBelow([Text::make('Note')]));
    $trailing = collect($payload['rows'])->firstWhere('isTrailingBlank', true);

    expect($trailing['below'])->toBeNull()
        ->and(unitsPayload(unitsGrid())['rows'][0]['below'])->toBeNull();
});
