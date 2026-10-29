<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Waguilar\FilamentDataGrid\DataGrid;

it('runs afterStateUpdated for the edited row and re-renders its cells', function (): void {
    $component = mountLines(
        [['product_id' => null, 'quantity' => null], ['product_id' => '2', 'quantity' => 5]],
        fn (): array => [
            DataGrid::make('lines')->schema([
                Select::make('product_id')
                    ->options(['1' => 'Tea', '2' => 'Coffee'])
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('quantity', 1)),
                TextInput::make('quantity')->numeric(),
                TextEntry::make('summary')->state(fn (Get $get): string => "{$get('product_id')}x{$get('quantity')}"),
            ]),
        ],
    );

    [$first, $second] = rowKeys($component);

    $component->set("data.lines.{$first}.product_id", '1');

    $rows = grid($component)->getGridPayload()['rows'];

    expect($component->get("data.lines.{$first}.quantity"))->toEqual(1)
        ->and($component->get("data.lines.{$second}.quantity"))->toEqual(5)
        ->and($rows[0]['cells']['summary']['html'])->toContain('1x1')
        ->and($rows[0]['cells']['product_id']['label'])->toBe('Tea');
});
