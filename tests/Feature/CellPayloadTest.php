<?php

declare(strict_types=1);

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\Cells\CellEditorType;
use Waguilar\FilamentDataGrid\Cells\ResolveCellEditorType;
use Waguilar\FilamentDataGrid\DataGrid;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedCellComponent;

/**
 * @return array<string, mixed>
 */
function cellOf(DataGrid $grid, int $row, string $field): array
{
    return $grid->getGridPayload()['rows'][$row]['cells'][$field];
}

it('turns each component into a column with its editor', function (): void {
    $component = mountLines([], fn (): array => [
        DataGrid::make('lines')
            ->table([
                TableColumn::make('Name')->markAsRequired(),
                TableColumn::make('Quantity')->width('8rem')->alignEnd(),
                TableColumn::make('Product'),
                TableColumn::make('Unit'),
                TableColumn::make('Active'),
                TableColumn::make('Total'),
            ])
            ->schema([
                TextInput::make('name'),
                TextInput::make('quantity')->integer(),
                Select::make('product_id')->searchable()->getSearchResultsUsing(fn (): array => []),
                Select::make('unit')->options(['kg' => 'Kilogram']),
                Toggle::make('active'),
                TextEntry::make('total'),
                Hidden::make('reference'),
            ]),
    ]);

    $columns = grid($component)->getGridPayload()['columns'];

    expect(array_column($columns, 'field'))->toBe(['name', 'quantity', 'product_id', 'unit', 'active', 'total'])
        ->and(array_map(fn (array $column): string => $column['editor']['type'], $columns))
        ->toBe(['text', 'number', 'select', 'select', 'toggle', 'entry'])
        ->and($columns[0]['required'])->toBeTrue()
        ->and($columns[1])->toMatchArray(['label' => 'Quantity', 'width' => '8rem', 'alignment' => 'end'])
        ->and($columns[1]['editor']['integer'])->toBeTrue();
});

it('labels columns from their components when no table columns are given', function (): void {
    $component = mountLines([], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('unit_price')->required()]),
    ]);

    expect(grid($component)->getGridPayload()['columns'][0])
        ->toMatchArray(['label' => 'Unit price', 'required' => true]);
});

it('evaluates closures in the scope of each row', function (): void {
    $component = mountLines(
        [['unit' => 'box', 'quantity' => 2, 'note' => 'x'], ['unit' => 'kg', 'quantity' => null, 'note' => null]],
        fn (): array => [
            DataGrid::make('lines')->schema([
                TextInput::make('unit'),
                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(fn (Get $get): float => $get('unit') === 'box' ? 1.0 : 0.001)
                    ->step(fn (Get $get): int|string => $get('unit') === 'box' ? 1 : 'any')
                    ->required(fn (Get $get): bool => $get('unit') === 'box')
                    ->disabled(fn (Get $get): bool => $get('unit') === 'kg'),
                TextInput::make('note')->visible(fn (Get $get): bool => filled($get('note'))),
            ]),
        ],
    );

    $grid = grid($component);

    expect(cellOf($grid, 0, 'quantity'))->toMatchArray(['min' => 1.0, 'step' => 1, 'required' => true, 'disabled' => false])
        ->and(cellOf($grid, 1, 'quantity'))->toMatchArray(['min' => 0.001, 'step' => 'any', 'required' => false, 'disabled' => true])
        ->and(cellOf($grid, 0, 'note')['visible'])->toBeTrue()
        ->and(cellOf($grid, 1, 'note'))->toBe(['visible' => false]);
});

it('shows the select label from getOptionLabelUsing', function (): void {
    $component = mountLines([['product_id' => '7']], fn (): array => [
        DataGrid::make('lines')->schema([
            Select::make('product_id')
                ->searchable()
                ->getSearchResultsUsing(fn (): array => [])
                ->getOptionLabelUsing(fn (mixed $value): string => "Product #{$value}"),
        ]),
    ]);

    expect(cellOf(grid($component), 0, 'product_id')['label'])->toBe('Product #7');
});

it('sends static options per row with disabled options marked', function (): void {
    $component = mountLines([['unit' => 'kg', 'taken' => 'box']], fn (): array => [
        DataGrid::make('lines')->schema([
            Select::make('unit')
                ->options(['box' => 'Box', 'kg' => 'Kilogram'])
                ->disableOptionWhen(fn (string $value, Get $get): bool => $value === $get('taken')),
            Hidden::make('taken'),
        ]),
    ]);

    expect(cellOf(grid($component), 0, 'unit'))->toMatchArray([
        'label' => 'Kilogram',
        'options' => [
            ['label' => 'Box', 'value' => 'box', 'isDisabled' => true],
            ['label' => 'Kilogram', 'value' => 'kg', 'isDisabled' => false],
        ],
    ]);
});

it('renders infolist entries per row with Filament markup', function (): void {
    $component = mountLines([['price' => 2, 'quantity' => 3], ['price' => 5, 'quantity' => 1]], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('price'),
            TextInput::make('quantity'),
            TextEntry::make('total')
                ->state(fn (Get $get): float => (float) $get('price') * (float) $get('quantity'))
                ->money('USD'),
        ]),
    ]);

    $grid = grid($component);

    expect(cellOf($grid, 0, 'total')['html'])->toContain('fi-in-text')->toContain('$6.00')
        ->and(cellOf($grid, 1, 'total')['html'])->toContain('$5.00');
});

it('edits fields it cannot show inline in a modal', function (): void {
    expect(app(ResolveCellEditorType::class)->handle(ColorPicker::make('color')))->toBe(CellEditorType::Modal);
});

it('refuses layout components as cells', function (): void {
    app(ResolveCellEditorType::class)->handle(Section::make());
})->throws(UnsupportedCellComponent::class);

it('keeps hidden fields in the row state without a column', function (): void {
    $component = mountLines([['name' => 'a', 'reference' => 'ref-1']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('name'), Hidden::make('reference')]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect(array_column($payload['columns'], 'field'))->toBe(['name'])
        ->and($payload['rows'][0]['state'])->toBe(['name' => 'a', 'reference' => 'ref-1'])
        ->and($component->instance()->form->getState()['lines'])->toBe([['name' => 'a', 'reference' => 'ref-1']]);
});

it('freezes the leading columns, capped at the number of columns', function (int $requested, int $frozen): void {
    $component = mountLines([], fn (): array => [
        DataGrid::make('lines')->frozenColumns($requested)->schema([TextInput::make('a'), TextInput::make('b')]),
    ]);

    expect(grid($component)->getGridPayload()['frozenColumns'])->toBe($frozen);
})->with([
    'none' => [0, 0],
    'one' => [1, 1],
    'more than there are' => [5, 2],
]);
