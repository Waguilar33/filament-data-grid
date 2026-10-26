# filament-data-grid

An editable data grid for Filament 5 forms. `DataGrid` is a drop-in for `Repeater::make()->table()`: its cells are Filament's own form components and infolist entries, and their closures run per row on the server.

```php
use Filament\Forms\Components\Repeater\TableColumn;
use Waguilar33\FilamentDataGrid\DataGrid;

DataGrid::make('lines')
    ->trailingEmptyRow(filledBy: 'product_id')
    ->frozenColumns(1)
    ->table([
        TableColumn::make(__('Product'))->markAsRequired(),
        TableColumn::make(__('Quantity'))->width('8rem')->alignEnd(),
        TableColumn::make(__('Total'))->width('8rem')->alignEnd(),
    ])
    ->schema([
        Select::make('product_id')
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => /* ... */)
            ->getOptionLabelUsing(fn ($value): ?string => /* ... */)
            ->required()
            ->live(),
        TextInput::make('quantity')->numeric()->minValue(1)->live(),
        TextEntry::make('total')->state(fn (Get $get) => /* ... */)->money('GTQ'),
        Hidden::make('order_line_id'),
    ]);
```

Read [CONTEXT.md](CONTEXT.md) for the goal and [docs/decisions.md](docs/decisions.md) for how it is built and why.

## Try it

```bash
composer install
npm install
composer serve
```

Open http://127.0.0.1:8000/demo/data-grid-demo. Use the header link to switch between English and Spanish.

## Develop

```bash
npm run dev          # rebuild resources/dist on change
vendor/bin/testbench filament:assets   # republish the built assets to the demo
composer test        # Pest, including browser tests (run `npx playwright install chromium` once)
composer analyse     # PHPStan level 10
composer format      # Pint
```

Run `npm run build` and commit `resources/dist` with every JS or CSS change.
