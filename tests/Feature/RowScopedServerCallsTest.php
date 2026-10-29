<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\DataGrid;

it('runs a cell server method in the scope of its own row', function (): void {
    $component = mountLines(
        [['product_id' => null, 'prefix' => 'first'], ['product_id' => null, 'prefix' => 'second']],
        fn (): array => [
            DataGrid::make('lines')->schema([
                Select::make('product_id')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search, Get $get): array => ['p1' => "{$get('prefix')}:{$search}"]),
                TextInput::make('prefix'),
            ]),
        ],
    );

    $secondRow = rowKeys($component)[1];
    $selectKey = grid($component)->getChildSchema($secondRow)?->getComponent('product_id')?->getKey();

    expect($selectKey)->toBeString()
        ->and($component->instance()->callSchemaComponentMethod((string) $selectKey, 'getSearchResultsForJs', ['search' => 'tea']))
        ->toBe([['label' => 'second:tea', 'value' => 'p1', 'isDisabled' => false]]);
});
