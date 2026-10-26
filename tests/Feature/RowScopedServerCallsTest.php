<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\View;
use Waguilar33\FilamentDataGrid\DataGrid;
use Waguilar33\FilamentDataGrid\Tests\Fixtures\LinesForm;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    View::addNamespace('filament-data-grid-tests', __DIR__.'/../Fixtures/views');

    LinesForm::$initialLines = [
        ['product_id' => null, 'prefix' => 'first'],
        ['product_id' => null, 'prefix' => 'second'],
    ];

    LinesForm::$configureUsing = fn (): array => [
        DataGrid::make('lines')->schema([
            Select::make('product_id')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search, Get $get): array => [
                    'p1' => "{$get('prefix')}:{$search}",
                ]),
            TextInput::make('prefix'),
        ]),
    ];
});

it('runs a cell server method in the scope of its own row', function (): void {
    $component = livewire(LinesForm::class);

    $rowKeys = array_keys($component->get('data.lines'));
    $grid = $component->instance()->form->getComponent('lines');

    $selectKey = $grid->getChildSchema($rowKeys[1])->getComponent('product_id')->getKey();

    expect($component->instance()->callSchemaComponentMethod($selectKey, 'getSearchResultsForJs', ['search' => 'tea']))
        ->toBe([['label' => 'second:tea', 'value' => 'p1', 'isDisabled' => false]]);
});
