<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Waguilar33\FilamentDataGrid\DataGrid;

it('disables every cell and row action when the grid is disabled', function (): void {
    $component = mountLines([['name' => 'a', 'unit' => 'kg']], fn (): array => [
        DataGrid::make('lines')->disabled()->schema([
            TextInput::make('name'),
            Select::make('unit')->options(['kg' => 'Kilogram']),
        ]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect($payload['rows'][0]['cells']['name']['disabled'])->toBeTrue()
        ->and($payload['rows'][0]['cells']['unit']['disabled'])->toBeTrue()
        ->and($payload['rows'][0]['actions'])->toBeNull()
        ->and($payload['reorderable'])->toBeFalse();
});
