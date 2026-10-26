<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Waguilar33\FilamentDataGrid\DataGrid;

it('reorders rows with the repeater reorder action', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'b'], ['name' => 'c']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('name')]),
    ]);

    [$a, $b, $c] = rowKeys($component);

    expect(grid($component)->getGridPayload()['reorderable'])->toBeTrue();

    $component->callAction(TestAction::make('reorder')->schemaComponent('lines')->arguments(['items' => [$c, $a, $b]]));

    expect(array_column(array_values($component->get('data.lines')), 'name'))->toBe(['c', 'a', 'b']);
});

it('can turn reordering off', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')->reorderable(false)->schema([TextInput::make('name')]),
    ]);

    expect(grid($component)->getGridPayload()['reorderable'])->toBeFalse();
});
