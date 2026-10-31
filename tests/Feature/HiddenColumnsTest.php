<?php

declare(strict_types=1);

use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\DataGrid;

function columnFields(array $payload): array
{
    return array_column($payload['columns'], 'field');
}

it('leaves out a column whose field is hidden in every row and keeps its neighbours’ headers', function (): void {
    $component = mountLines([['name' => 'A', 'code' => 'X', 'qty' => 1], ['name' => 'B', 'code' => 'Y', 'qty' => 2]], fn (): array => [
        DataGrid::make('lines')
            ->table([TableColumn::make('Name'), TableColumn::make('Code'), TableColumn::make('Quantity')])
            ->schema([TextInput::make('name'), TextInput::make('code')->visible(false), TextInput::make('qty')]),
    ]);

    $columns = grid($component)->getGridPayload()['columns'];

    expect(array_column($columns, 'field'))->toBe(['name', 'qty'])
        ->and(array_column($columns, 'label'))->toBe(['Name', 'Quantity']);
});

it('leaves out a column whose per-row closure hides it in every row', function (): void {
    $component = mountLines([['name' => 'A', 'coded' => false], ['name' => 'B', 'coded' => false]], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('name'),
            TextInput::make('code')->visible(fn (Get $get): bool => (bool) $get('coded')),
        ]),
    ]);

    expect(columnFields(grid($component)->getGridPayload()))->toBe(['name']);
});

it('keeps a column shown in at least one row, with an empty hidden cell in the others', function (): void {
    $component = mountLines([['name' => 'A', 'coded' => false], ['name' => 'B', 'coded' => true]], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('name'),
            TextInput::make('code')->visible(fn (Get $get): bool => (bool) $get('coded')),
        ]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect(columnFields($payload))->toBe(['name', 'code'])
        ->and($payload['rows'][0]['cells']['code'])->toBe(['visible' => false]);
});

it('uses the template schema’s visibility when there are no rows', function (): void {
    $component = mountLines([], fn (): array => [
        DataGrid::make('lines')->defaultItems(0)->schema([
            TextInput::make('name'),
            TextInput::make('code')->visible(false),
        ]),
    ]);

    expect(columnFields(grid($component)->getGridPayload()))->toBe(['name']);
});
