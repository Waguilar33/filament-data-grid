<?php

declare(strict_types=1);

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Waguilar33\FilamentDataGrid\DataGrid;

function linesWithTrailingRow(): Closure
{
    return fn (): array => [
        DataGrid::make('lines')
            ->trailingEmptyRow(filledBy: 'product_id')
            ->schema([
                Select::make('product_id')->options(['1' => 'Tea', '2' => 'Coffee'])->required()->live(),
                TextInput::make('quantity')->numeric()->default(1)->required(),
            ]),
    ];
}

it('appends one blank row after the filled rows', function (): void {
    $component = mountLines([['product_id' => '1', 'quantity' => 2]], linesWithTrailingRow());

    $keys = rowKeys($component);

    expect($keys)->toHaveCount(2)
        ->and($component->get("data.lines.{$keys[1]}"))->toEqual(['product_id' => null, 'quantity' => 1]);
});

it('does not add another blank row on re-render', function (): void {
    $component = mountLines([['product_id' => '1', 'quantity' => 2]], linesWithTrailingRow());

    $component->call('$refresh')->call('$refresh');

    expect(rowKeys($component))->toHaveCount(2);
});

it('appends a new blank row once the last row is filled', function (): void {
    $component = mountLines([], linesWithTrailingRow());
    $trailing = rowKeys($component)[0];

    $component->set("data.lines.{$trailing}.product_id", '2');

    expect(rowKeys($component))->toHaveCount(2)
        ->and($component->get("data.lines.{$trailing}.product_id"))->toBe('2');
});

it('skips the blank row when validating and drops it from the saved state', function (): void {
    $component = mountLines([['product_id' => '1', 'quantity' => 2]], linesWithTrailingRow());

    $component->call('save')->assertHasNoErrors();

    expect($component->instance()->form->getState()['lines'])->toEqual([['product_id' => '1', 'quantity' => 2]]);
});

it('counts editable fields without a default as the ones that fill a row', function (): void {
    $component = mountLines([], fn (): array => [
        DataGrid::make('lines')
            ->trailingEmptyRow()
            ->schema([
                TextInput::make('name'),
                TextInput::make('quantity')->default(1),
                Toggle::make('active'),
                Hidden::make('reference'),
            ]),
    ]);

    expect(grid($component)->getTrailingEmptyRowFilledBy())->toBe(['name']);
});
