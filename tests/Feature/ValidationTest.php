<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\DataGrid;

it('puts server errors on the cell of the right row', function (): void {
    $component = mountLines(
        [['name' => 'a', 'quantity' => 1], ['name' => 'b', 'quantity' => null]],
        fn (): array => [
            DataGrid::make('lines')->schema([
                TextInput::make('name'),
                TextInput::make('quantity')->numeric()->required(),
            ]),
        ],
    );

    $keys = rowKeys($component);

    $component->call('save')->assertHasErrors(["data.lines.{$keys[1]}.quantity" => 'required']);

    $rows = grid($component)->getGridPayload()['rows'];

    expect($rows[0]['cells']['quantity'])->not->toHaveKey('error')
        ->and($rows[1]['cells']['quantity']['error'])->toBe('The quantity field is required.');
});

it('keeps an error on its row after an earlier row is deleted', function (): void {
    $component = mountLines(
        [['quantity' => 1], ['quantity' => 1], ['quantity' => null]],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('quantity')->required()])],
    );

    [$first, , $third] = rowKeys($component);

    $component->call('save')->assertHasErrors(["data.lines.{$third}.quantity"]);

    $component->callAction(TestAction::make('delete')->schemaComponent('lines')->arguments(['item' => $first]));

    $rows = grid($component)->getGridPayload()['rows'];

    expect(array_column($rows, 'key'))->not->toContain($first)
        ->and($rows[1]['key'])->toBe($third);
});

it('rejects an option disabled for the row when saving', function (): void {
    $component = mountLines([['product' => null, 'locked' => true]], fn (): array => [
        DataGrid::make('lines')->schema([
            Hidden::make('locked'),
            Select::make('product')
                ->options(['a' => 'Apple', 'b' => 'Banana'])
                ->disableOptionWhen(fn (string $value, Get $get): bool => $value === 'b' && $get->boolean('locked')),
        ]),
    ]);

    $key = rowKeys($component)[0];

    $component->set("data.lines.{$key}.product", 'b')->call('save')->assertHasErrors(["data.lines.{$key}.product"]);
});
