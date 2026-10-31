<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\RawJs;
use Waguilar\FilamentDataGrid\DataGrid;

function maskedAmountGrid(): DataGrid
{
    return DataGrid::make('lines')->schema([
        TextInput::make('name'),
        TextInput::make('amount')
            ->numeric()
            ->minValue(0)
            ->maxValue(1000)
            ->mask(RawJs::make('$money($input, \'.\', \',\', 3)'))
            ->stripCharacters(','),
    ]);
}

it('sends the mask and strip characters of a masked number cell', function (): void {
    $component = mountLines([['amount' => 12.5]], fn (): array => [maskedAmountGrid()]);

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['amount'])->toMatchArray([
        'mask' => '$money($input, \'.\', \',\', 3)',
        'maskDynamic' => true,
        'stripCharacters' => [','],
        'min' => 0,
        'max' => 1000,
    ]);
});

it('sends a plain string mask as a static mask', function (): void {
    $component = mountLines([['code' => 1234]], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('code')->numeric()->mask('9999')]),
    ]);

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['code'])->toMatchArray([
        'mask' => '9999',
        'maskDynamic' => false,
        'stripCharacters' => [],
    ]);
});

it('evaluates the mask for each row', function (): void {
    $component = mountLines(
        [['currency' => 'usd', 'amount' => 1], ['currency' => 'eur', 'amount' => 2]],
        fn (): array => [
            DataGrid::make('lines')->schema([
                TextInput::make('currency'),
                TextInput::make('amount')
                    ->numeric()
                    ->mask(fn (Get $get): RawJs => RawJs::make($get('currency') === 'eur' ? '$money($input, \',\', \'.\')' : '$money($input)'))
                    ->stripCharacters(fn (Get $get): string => $get('currency') === 'eur' ? '.' : ','),
            ]),
        ],
    );

    $rows = grid($component)->getGridPayload()['rows'];

    expect($rows[0]['cells']['amount'])->toMatchArray(['mask' => '$money($input)', 'stripCharacters' => [',']])
        ->and($rows[1]['cells']['amount'])->toMatchArray(['mask' => '$money($input, \',\', \'.\')', 'stripCharacters' => ['.']]);
});

it('leaves an unmasked number cell as it was', function (): void {
    $component = mountLines([['amount' => 1]], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('amount')->numeric()->minValue(0)->stripCharacters(',')]),
    ]);

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['amount'])
        ->not->toHaveKeys(['mask', 'maskDynamic', 'stripCharacters'])
        ->toHaveKey('min', 0);
});

it('stores the number the grid sends for a masked cell', function (): void {
    $component = mountLines([['name' => 'a', 'amount' => null]], fn (): array => [maskedAmountGrid()]);
    $key = rowKeys($component)[0];

    $component->set("data.lines.{$key}.amount", 999.5)->call('save')->assertHasNoErrors();

    expect($component->instance()->form->getState()['lines'][0]['amount'])->toBe(999.5);
});

it('strips the mask characters from masked text on the server', function (): void {
    $component = mountLines([['name' => 'a', 'amount' => null]], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('amount')
                ->numeric()
                ->maxValue(10000)
                ->mask(RawJs::make('$money($input, \'.\', \',\', 3)'))
                ->stripCharacters(','),
        ]),
    ]);
    $key = rowKeys($component)[0];

    $component->set("data.lines.{$key}.amount", '1,234.5')->call('save')->assertHasNoErrors();

    expect($component->instance()->form->getState()['lines'][0]['amount'])->toBe(1234.5);
});

it('validates the unmasked number against the minimum and maximum', function (mixed $amount, string $rule): void {
    $component = mountLines([['name' => 'a', 'amount' => null]], fn (): array => [maskedAmountGrid()]);
    $key = rowKeys($component)[0];

    $component->set("data.lines.{$key}.amount", $amount)
        ->call('save')
        ->assertHasErrors(["data.lines.{$key}.amount" => $rule]);

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['amount']['error'])->toContain('amount');
})->with([
    'over the maximum as text' => ['1,000.5', 'max'],
    'over the maximum as a number' => [1000.5, 'max'],
    'under the minimum' => [-1, 'min'],
]);
