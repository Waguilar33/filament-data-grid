<?php

declare(strict_types=1);

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\DataGrid;

it('edits radios and toggle buttons as Filament selects with their own options', function (): void {
    $component = mountLines([['kind' => 'credit', 'tags' => ['a', 'b'], 'locked' => 'debit']], fn (): array => [
        DataGrid::make('lines')->schema([
            Radio::make('kind')
                ->options(['credit' => 'Credit', 'debit' => 'Debit'])
                ->disableOptionWhen(fn (string $value, Get $get): bool => $value === $get('locked'))
                ->required(),
            ToggleButtons::make('tags')->options(['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma'])->multiple(),
            TextInput::make('locked'),
        ]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect($payload['columns'][0]['editor'])->toMatchArray(['type' => 'select'])
        ->and($payload['columns'][0]['editor']['select'])->toMatchArray(['isSearchable' => false, 'isMultiple' => false])
        ->and($payload['columns'][1]['editor']['select'])->toMatchArray(['isMultiple' => true])
        ->and($payload['rows'][0]['cells']['kind'])->toMatchArray([
            'label' => 'Credit',
            'canSelectPlaceholder' => false,
            'options' => [
                ['label' => 'Credit', 'value' => 'credit', 'isDisabled' => false],
                ['label' => 'Debit', 'value' => 'debit', 'isDisabled' => true],
            ],
        ])
        ->and($payload['rows'][0]['cells']['tags']['labels'])->toBe([
            ['label' => 'Alpha', 'value' => 'a'],
            ['label' => 'Beta', 'value' => 'b'],
        ]);
});

it('mirrors the select configuration Filament uses for the same field', function (): void {
    $component = mountLines([['product' => '7']], fn (): array => [
        DataGrid::make('lines')->schema([
            Select::make('product')
                ->searchable()
                ->allowHtml()
                ->getSearchResultsUsing(fn (): array => [])
                ->getOptionLabelUsing(fn (mixed $value): string => "<strong>#{$value}</strong>"),
        ]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect($payload['columns'][0]['editor']['select'])->toMatchArray([
        'isSearchable' => true,
        'isHtmlAllowed' => true,
        'hasDynamicSearchResults' => true,
        'searchPrompt' => 'Start typing to search',
    ])->and($payload['rows'][0]['cells']['product']['label'])->toBe('<strong>#7</strong>');
});

it('edits dates with the row limits and shows them in the display format', function (): void {
    $component = mountLines([['due' => '2026-10-26', 'at' => '2026-10-26 14:30:00']], fn (): array => [
        DataGrid::make('lines')->schema([
            DatePicker::make('due')
                ->minDate('2026-01-01')
                ->maxDate(fn (Get $get): string => '2026-12-31')
                ->displayFormat('d/m/Y'),
            DateTimePicker::make('at')->seconds(false),
        ]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect($payload['columns'][0]['editor'])->toMatchArray(['type' => 'date', 'inputType' => 'date', 'internalFormat' => 'Y-m-d'])
        ->and($payload['columns'][1]['editor'])->toMatchArray(['inputType' => 'datetime-local'])
        ->and($payload['rows'][0]['cells']['due'])->toMatchArray(['min' => '2026-01-01', 'max' => '2026-12-31', 'label' => '26/10/2026'])
        ->and($payload['rows'][0]['cells']['at']['label'])->toBe('Oct 26, 2026 14:30');
});

it('translates the number editor messages', function (string $locale, string $message): void {
    app()->setLocale($locale);

    $component = mountLines([], fn (): array => [DataGrid::make('lines')->schema([TextInput::make('quantity')->numeric()])]);

    expect(grid($component)->getGridPayload()['columns'][0]['editor']['messages']['min'])->toBe($message);
})->with([
    'English' => ['en', 'The :attribute field must be at least :min.'],
    'Spanish' => ['es', 'El campo :attribute debe ser al menos :min.'],
]);
