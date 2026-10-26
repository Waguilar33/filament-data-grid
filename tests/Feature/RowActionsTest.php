<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Waguilar33\FilamentDataGrid\DataGrid;

it('deletes a row by its key', function (): void {
    $component = mountLines(
        [['name' => 'a'], ['name' => 'b'], ['name' => 'c']],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')])],
    );

    [, $second] = rowKeys($component);

    $component->callAction(TestAction::make('delete')->schemaComponent('lines')->arguments(['item' => $second]));

    expect(array_column(array_values($component->get('data.lines')), 'name'))->toBe(['a', 'c']);
});

it('adds a row filled with component defaults', function (): void {
    $component = mountLines(
        [['name' => 'a', 'quantity' => 4]],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name'), TextInput::make('quantity')->default(1)])],
    );

    $component->callAction(TestAction::make('add')->schemaComponent('lines'));

    expect(array_values($component->get('data.lines')))->toEqual([
        ['name' => 'a', 'quantity' => 4],
        ['name' => null, 'quantity' => 1],
    ]);
});

it('renders a delete action per row and none on the trailing blank row', function (): void {
    $component = mountLines(
        [['name' => 'a']],
        fn (): array => [DataGrid::make('lines')->trailingEmptyRow()->schema([TextInput::make('name')])],
    );

    $rows = grid($component)->getGridPayload()['rows'];

    expect($rows[0]['actions'])->toContain("mountAction('delete'")
        ->and($rows[1]['actions'])->toBeNull();
});
