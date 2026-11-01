# Filament Data Grid

<picture class="filament-hidden">
  <source media="(prefers-color-scheme: dark)" srcset="art/screenshot-dark.png">
  <img src="art/screenshot-light.png" alt="Filament Data Grid">
</picture>

[![Tests](https://github.com/Waguilar33/filament-data-grid/actions/workflows/tests.yml/badge.svg)](https://github.com/Waguilar33/filament-data-grid/actions/workflows/tests.yml)
[![PHPStan](https://github.com/Waguilar33/filament-data-grid/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Waguilar33/filament-data-grid/actions/workflows/phpstan.yml)
[![License](https://img.shields.io/github/license/Waguilar33/filament-data-grid.svg?style=flat-square&label=license)](LICENSE.md)

An editable, spreadsheet-style data grid for Filament forms. Rows are typed in place, with the keyboard, the way people fill in order lines, invoices or stock counts.

`DataGrid` is a form field that extends Filament's `Repeater`, and you build it the same way as `Repeater::make()->table()`. Its cells are your own Filament form components. Read-only cells are infolist entries. Their closures run on the server for each row, so `Get`, `Set`, `live()`, `afterStateUpdated()`, `disabled()`, `required()` and validation rules all work. Validation errors use `Repeater`'s keys, and row actions are Filament Actions.

> [!WARNING]
> This plugin is in beta. You can install and use it, but its API may change before `v1.0.0`.

## Requirements

- PHP 8.2+
- Laravel 11.47+, 12 or 13.32+
- Filament 4.15.2+ or 5.10.2+

## Installation

```bash
composer require waguilar33/filament-data-grid:"^1.0@beta"
```

Until the package is on Packagist, add the GitHub repository to your app's `composer.json` first:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Waguilar33/filament-data-grid" }
]
```

Publish the plugin's assets:

```bash
php artisan filament:assets
```

If your app's `post-autoload-dump` scripts include `@php artisan filament:upgrade`, Filament runs this after every `composer update`. `filament:install` adds that script for you.

You don't need to change your theme or your Vite config. The grid ships its own compiled JavaScript and CSS. The JavaScript loads only on pages that render a grid. The stylesheet (about 3 KB gzipped) loads with every Filament page, so the grid never shifts the page when it appears.

## Basic usage

```php
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\DataGrid;

DataGrid::make('lines')
    ->table([
        TableColumn::make('Product')->markAsRequired(),
        TableColumn::make('Quantity')->width('8rem')->alignEnd(),
        TableColumn::make('Total')->width('8rem')->alignEnd(),
    ])
    ->schema([
        Select::make('product_id')
            ->options(Product::pluck('name', 'id'))
            ->searchable()
            ->required()
            ->live(),
        TextInput::make('quantity')
            ->numeric()
            ->minValue(1)
            ->required()
            ->live(),
        TextEntry::make('total')
            ->state(fn (Get $get): float => (Product::find($get('product_id'))?->price ?? 0) * (float) $get('quantity'))
            ->money('USD'),
        Hidden::make('id'),
    ])
```

Each `TableColumn` describes one visible component of the schema, in order. `Hidden` fields carry state without taking a column.

Like `Repeater`, the state is an array of rows, and `relationship()` saves them to a `HasMany` relationship:

```php
DataGrid::make('lines')
    ->relationship()
    ->table([...])
    ->schema([...])
```

`Repeater`'s other options still apply. Among them:

- `addable()`
- `deletable()`
- `reorderable()`
- `defaultItems()`
- `minItems()`
- `maxItems()`
- `addActionLabel()`
- `extraItemActions()`
- `disabled()`
- `hiddenLabel()`

## Cells

The grid chooses how to edit each cell from the component you put in the schema:

| Component | In the grid |
| --- | --- |
| `TextInput` | Edited in place. With `numeric()` it aligns right, checks `minValue()`, `maxValue()`, `integer()` and `step()` as you type, and steps like a native number input (by `step()`, or 1 when the step is `'any'`), staying within the min and max: with ArrowUp and ArrowDown, or with the up and down buttons at the end of the open editor. |
| `Select` | Filament's own select dropdown. It supports search, the clear button, `allowHtml()`, `multiple()`, `getSearchResultsUsing()`, `getOptionLabelUsing()` and `disableOptionWhen()`. |
| `Radio`, `ToggleButtons` | The same dropdown, listing their options. |
| `Toggle`, `Checkbox` | A switch you click, or flip with Space. |
| `DatePicker`, `DateTimePicker`, `TimePicker` | The browser's date or time input. It respects `minDate()`, `maxDate()` and `displayFormat()`. |
| Any other field | Opens in a modal for that row. For example `ColorPicker`, `Textarea` or your own custom field. |
| An infolist entry, such as `TextEntry` | Read-only. Rendered with Filament's own entry view, so formatting such as `money()` works. |

Editable cells look like text, not input boxes, and still show what you can change:

- **The value is underlined with a dashed line.** On hover, or when the cell is selected with the keyboard, the line turns solid in the primary color and the cell lightens slightly. The cell is outlined only while its editor is open. An empty editable cell shows the field's `placeholder()`, or a short underline when it has none. Selects underline their selected label. Toggles have no underline.
- **A pencil follows the header label** of every column that is editable in at least one row. It uses the `CELL_EDIT` icon alias. The trailing empty row counts, because you can type in it.
- **Locked cells read as plain text**, with no underline and no icon. A cell is locked when its field is `disabled()` or `readOnly()`. A read-only field doesn't open an editor.

To also tint the editable cells of a column, use `highlightEditableColumns()`. It is off by default. Pass `true` for every column, or a list of field names. Locked cells are not tinted.

```php
DataGrid::make('places')
    ->highlightEditableColumns(['quantity'])
```

A field's own `suffixIcon()` is shown after the value of its editable cells, faint at rest and in the primary color on hover, for example a barcode icon on a code you can scan. It can be a closure, evaluated for each row. Locked cells don't show it, and the grid doesn't add any icon of its own. Date and modal cells show it instead of their calendar or pencil.

```php
TextInput::make('code')
    ->placeholder('Scan or type')
    ->suffixIcon('fal-sharp-barcode-read')
```

A field hidden in every row, with `visible(false)` or a closure that is false for all rows, gets no column. A field hidden in only some rows keeps its column and leaves those cells empty. With no rows, the field's visibility in the row template decides.

A component that is neither a form field nor an infolist entry throws `Waguilar\FilamentDataGrid\Exceptions\UnsupportedCellComponent`.

Rows grow to fit their tallest cell, with 44px as the minimum, so an entry with `belowContent()` or a long name that wraps is never cut off. This applies to child rows too. The grid measures the rows again when the state changes, when a row opens or closes, and when the grid is resized.

### Heading, description and icon

Like `DataGridEntry`, the editable grid can draw its own Filament section header, so it doesn't need wrapping in a `Section`, which would show a card inside a card:

```php
DataGrid::make('products')
    ->hiddenLabel()
    ->heading('Stock to allocate')
    ->description('Assign how many units leave each place.')
    ->icon(Heroicon::OutlinedArchiveBox)
    ->compact()
```

The header uses Filament's section markup, and the columns line up with it as they do in `DataGridEntry`: 24px from `sm` up (16px below), or always 16px with `compact()`. A grid without a heading, description or icon looks as before.

### Per-row logic

Closures run once per row, with that row's state:

```php
TextInput::make('comment')
    ->disabled(fn (Get $get): bool => blank($get('product_id'))),

TextInput::make('quantity')
    ->numeric()
    ->minValue(fn (Get $get): float => $get('unit') === 'box' ? 1 : 0.001)
    ->required(fn (Get $get): bool => filled($get('product_id'))),

Select::make('product_id')
    ->live()
    ->afterStateUpdated(fn (Set $set) => $set('quantity', 1)),
```

### Masked numbers

A numeric `TextInput` keeps its `mask()` and `stripCharacters()` in the grid. The cell's editor uses Filament's own Alpine mask, so the value is masked as you type or paste, and the cell shows the same masked text when you are not editing it. On commit, the strip characters are removed and the cell stores a number, which `minValue()` and `maxValue()` then check.

```php
TextInput::make('counted')
    ->numeric()
    ->maxValue(10_000_000)
    ->mask(RawJs::make("\$money(\$input, '{$decimal}', '{$thousands}', 3)"))
    ->stripCharacters($thousands),
```

With `$decimal = '.'` and `$thousands = ','`, typing 1234567 shows "1,234,567" and is saved as `1234567`. When the decimal separator is a comma, list the dot in `stripCharacters()`. The grid then shows a stored `1234.5` as "1.234,5". A plain Filament `TextInput` with that mask shows the same value as "12.345". The grid differs because it redraws each cell from the stored number after every commit, so it must give the mask that number with the decimal separator the mask expects, or a saved "1.234,5" would come back as "12.345" and be stored as 12345 on the next edit. Masks on non-numeric cells are ignored.

### Hiding a column header

Use `hiddenHeaderLabel()` on the `TableColumn`, as with `Repeater`. Screen readers still read the header.

## Keyboard

| Key | Action |
| --- | --- |
| Arrow keys | Move between cells. |
| Typing | Start editing a text or number cell, or search a dropdown. |
| F2 | Edit the active cell. |
| Enter on a dropdown or modal cell | Open it. |
| Enter | Save and move down a row. |
| Shift + Enter | Save and move up a row. |
| Tab / Shift + Tab | Save and move right or left. |
| Escape | Cancel the edit. It does not close a surrounding modal. |
| Space | Flip a toggle. |

## Trailing empty row

For fast data entry, keep an empty row at the bottom of the grid. Typing in it turns it into a real row, and a new empty row appears below:

```php
DataGrid::make('lines')
    ->trailingEmptyRow()
```

The empty row is never validated or saved, and it doesn't count toward `minItems()` or `maxItems()`. Once the grid reaches `maxItems()`, no new empty row is added. By default, it becomes a real row when any editable field without a `default()` gets a value. You can choose which fields count by passing `filledBy`:

```php
DataGrid::make('lines')
    ->trailingEmptyRow(filledBy: 'product_id')

DataGrid::make('lines')
    ->trailingEmptyRow(filledBy: ['product_id', 'description'])
```

When the grid has a trailing empty row, the add button is hidden.

## Frozen columns

Keep the first columns in place while the rest scroll sideways:

```php
DataGrid::make('lines')
    ->frozenColumns(1)
```

When the grid is reorderable, the count includes the drag handle column.

## Row actions

Each row has a menu with the delete action and any `extraItemActions()`. These are normal Filament Actions, so they can open modals and slide-overs. The row's key is in `$arguments['item']`:

```php
use Filament\Actions\Action;

DataGrid::make('lines')
    ->extraItemActions([
        Action::make('stock')
            ->icon(Heroicon::OutlinedCube)
            ->slideOver()
            ->schema(fn (array $arguments, DataGrid $component): array => [
                TextEntry::make('available')
                    ->state(fn () => Stock::for($component->getRawItemState($arguments['item'])['product_id'])),
            ]),
    ])
```

If delete is the only action, it appears as a button instead of a menu.

## Reordering

As with `Repeater`, the grid is reorderable by default. Rows are dragged by their handle, and `orderColumn()` works with relationships. To turn reordering off:

```php
DataGrid::make('lines')
    ->reorderable(false)
```

## Editable child rows

A `DataGrid` row can open to show an editable table of its own child rows. Put a nested `DataGrid` in the parent's schema. It doesn't take a column, and it is built like any other `DataGrid`:

```php
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Waguilar\FilamentDataGrid\DataGrid;

DataGrid::make('products')
    ->addable(false)
    ->deletable(false)
    ->reorderable(false)
    ->expandRowsWhen(fn (Get $get): bool => collect($get('places'))->sum('quantity') < $get('requested'))
    ->table([
        TableColumn::make('Product'),
        TableColumn::make('Requested')->alignEnd(),
        TableColumn::make('Assigned')->alignEnd(),
    ])
    ->schema([
        Hidden::make('id'),
        TextEntry::make('name'),
        TextEntry::make('requested')->numeric(),
        TextEntry::make('assigned')->state(fn (Get $get): float => collect($get('places'))->sum('quantity')),
        DataGrid::make('places')
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->table([
                TableColumn::make('Place'),
                TableColumn::make('Available')->alignEnd(),
                TableColumn::make('Quantity')->alignEnd(),
            ])
            ->schema([
                TextEntry::make('place'),
                TextEntry::make('available')->numeric(),
                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn (Get $get): mixed => $get('available'))
                    ->live(),
            ]),
    ])
```

- **Which child grid:** a row shows the first nested `DataGrid` in its schema that is visible for that row. Use `visible()` to give different rows different child tables, such as quantities per place for some products and a code per unit for others. A row whose nested grids are all hidden has no child rows.
- **State:** the nested grid's state lives inside its parent row, with `Repeater`'s nested keys: `data.products.{uuid}.places.{uuid}.quantity`. The parent row's closures see it through `Get`, so a parent cell such as `assigned` updates when a child cell changes.
- **Validation:** errors use those nested keys. The invalid child cell shows its message, and its parent row is marked as having errors in its child rows.
- **Opening rows:** `expandRowsWhen()` takes a boolean or a closure that runs for each row. The closure receives the row's raw state as `array $state`, and `Get $get` reads that row's fields. Rows are closed by default.
- **Spacing:** the child table sits 15 pixels in from every side of the open row; change that with `childTablePadding(8)`.

### Content above and below the child grid

`expandedAbove()` and `expandedBelow()` work as on [`DataGridEntry`](#content-above-and-below-the-child-rows). An open row shows your components above and below its child grid, in the same inset panel. Pass an array, or a closure that runs for each row. It gets the row's `array $state`, and `Get $get` reads that row's fields. Return an empty array or `null` for no content. A row that has content but no visible nested grid still opens, so it can explain why there is nothing to fill in. Without a nested grid, the content sits straight in the open row, without the panel:

```php
use Filament\Schemas\Components\Text;

DataGrid::make('products')
    ->expandedBelow(fn (Get $get): ?array => $get('purchase_order')
        ? [Text::make("The stock arrives when purchase order {$get('purchase_order')} is received.")]
        : null)
    ->schema([
        Hidden::make('purchase_order'),
        // ...
        DataGrid::make('units')->visible(fn (Get $get): bool => ! $get('purchase_order')),
    ])
```

The content is for display: it renders with the row's data, but it is not part of the form, so put fields in the grid's columns or in its child grid instead. The trailing empty row has no content.

## Read-only grid with child rows

`DataGridEntry` is an infolist entry that shows rows in the same grid, read-only. Each row can open to show its own child rows, with their own columns. It extends Filament's `RepeatableEntry`, so you build it the same way as `RepeatableEntry::make()->table()`:

```php
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Waguilar\FilamentDataGrid\DataGridEntry;

DataGridEntry::make('deliveries')
    ->state(fn (): array => $deliveries)
    ->searchable(['delivery_number', 'customer'])
    ->paginated([10, 25, 50])
    ->table([
        TableColumn::make('Delivery'),
        TableColumn::make('Customer'),
        TableColumn::make('Warehouse')->width('12rem'),
    ])
    ->schema([
        TextEntry::make('delivery_number'),
        TextEntry::make('customer'),
        TextEntry::make('fill_status')->badge(),
        RepeatableEntry::make('lines')
            ->table([
                TableColumn::make('Product'),
                TableColumn::make('Requested')->alignEnd(),
                TableColumn::make('Handed over')->alignEnd(),
            ])
            ->schema([
                TextEntry::make('product'),
                TextEntry::make('requested')->numeric(),
                TextEntry::make('handed_over')->numeric(),
            ]),
    ])
```

The state can be an array, a collection or a relationship, as with `RepeatableEntry`.

- **Child rows** come from the first `RepeatableEntry` in the schema that has a `table()`. It doesn't take a column. Its rows open under their parent row as a small table of their own, with its own column headers. Without one, the grid has no child rows. The child table sits 15 pixels in from every side of the open row; change that with `childTablePadding(8)`.
- **Child columns per row:** each open row's child table leaves out the columns that are hidden in all of that row's child rows, so one `table()` can list the detail fields of every kind of child. For example, `TextEntry::make('bank')->visible(fn (?string $state): bool => filled($state))` shows a Bank column only under the parents that have a child with a bank. A column shown in at least one child row stays for the whole child table, with empty cells where it is hidden. Inside a nested entry, read the child row with `$component->getContainer()->getConstantState()` or the entry's own `$state`: `Get` doesn't reach it.
- **Opening rows:** rows start closed. Click a row, or its arrow button, to open or close it. Several rows can be open at once.
- **Search:** `searchable()` adds a search field that filters parent rows. A row's child rows stay with it. Pass a list of fields to search only those columns. The search matches the text the entries show, such as a badge's formatted label.
- **Pagination:** `paginated([10, 25, 50])` pages parent rows and lets users pick the page size, with Filament's own pagination controls. Add `DataGridEntry::ALL_RECORDS` to offer "All", and use `defaultPaginationPageOption(25)` to start on another size. `paginated(10)` gives a fixed size, and `paginated()` uses 10, 25 and 50. Open child rows don't count toward the page size, so a parent row and its children are never split across pages.

### Content above and below the child rows

An open row can show your own content above and below its child table, such as the delivery's address and phone above its products. Pass any Filament schema components; they use the row's data, like entries in an infolist:

```php
DataGridEntry::make('deliveries')
    ->expandedAbove([
        Grid::make(3)->schema([
            TextEntry::make('address'),
            TextEntry::make('phone'),
            TextEntry::make('route')->badge(),
        ]),
    ])
    ->expandedBelow(fn (array $state): array => filled($state['notes'] ?? null)
        ? [TextEntry::make('notes')]
        : [])
```

A closure receives the row's `$state` (and `$record` for Eloquent rows) and returns the components for that row, or an empty array for none. A row with content above or below, but no child table, still opens. The content sits inside the same inset panel as the child table, and the row grows to fit it.

### A full Filament table as the child rows

When the child rows need their own search, filters, pagination, bulk selection or actions, give each open row a real Filament table instead of a nested `RepeatableEntry`. Write it as a Livewire component that extends `ChildTable`, and pass it to `childTable()` with the props for each parent row:

```php
use Waguilar\FilamentDataGrid\ChildTable;

class ShiftMovementsTable extends ChildTable
{
    public string $method;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Movement::query()->where('payment_method', $this->method))
            ->columns([
                TextColumn::make('time'),
                TextColumn::make('reference')->searchable(),
                TextColumn::make('card')->visible(fn (): bool => $this->method !== 'cash'),
                TextColumn::make('amount')->money('USD'),
            ])
            ->filters([SelectFilter::make('type')->options([...])])
            ->recordActions([Action::make('receipt')->action(...)])
            ->toolbarActions([BulkAction::make('reconcile')->action(...)])
            ->paginated([10, 25, 50, 'all']);
    }
}

DataGridEntry::make('payment_methods')
    ->table([...])
    ->schema([...])
    ->childTable(ShiftMovementsTable::class, fn (array $record): array => ['method' => $record['key']])
```

Livewire must be able to find the component on later requests. On Livewire 3 that means it lives in your Livewire namespace (`App\Livewire` by default) or you register it with `Livewire::component()`; Livewire 4 finds any class.

`ChildTable` is a Livewire component with Filament's `HasTable`, `HasSchemas` and `HasActions` already set up. Anything a Filament table does works inside it: column `visible()` closures that read its props (so each parent can show different columns), its own search field and filters, the per-page select, "select all on this page", bulk actions, and record actions with modals and slide-overs. Any other Livewire component works too.

- The closure receives the row's `$record` (the Eloquent model, or the row's array) and `$state`, and returns the component's props. A component with a public `$record` property, as `ChildTable` has, also gets the parent model.
- Each parent row on the page gets its own component, mounted lazily: it loads the first time its row is opened and scrolled into view, then keeps its search, filters and page while the row is closed and reopened, and while the page around it re-renders.
- The parent row grows and shrinks with the child table as it pages, filters or switches to "all", and moves the rows below it.
- It works in a standalone `DataGridEntry` (browser mode) and in `toTableContent()`, where Filament pages the parents and the children follow. It can't be combined with `serverSide()`: pages loaded that way don't go through Livewire's render, so they can't mount components, and the grid throws `UnsupportedChildTable`.
- Filament remembers a table's per-page choice in the session by component class, so every child table of the same class opens with the per-page the user last picked in any of them.
- `childTable()` replaces a nested `RepeatableEntry` child table. `expandedAbove()` and `expandedBelow()` still show above and below it.

### Column widths

Columns fit their content, the way an HTML table does. The browser measures the header and the cells of the rows on the current page, each at its widest (on one line) and at its narrowest (wrapped wherever the text can break, such as at spaces and hyphens, with each badge, icon and action kept whole):

- When every column fits on one line, each column gets its widest width, and the spare room goes to the columns in proportion to that width. Long text, such as a name or an address, gets most of it, and short columns, such as an icon or a date, stay narrow.
- When they don't fit, text wraps: each column shrinks towards its narrowest width, in proportion to how much it can shrink.
- When even the narrowest widths don't fit, the grid scrolls sideways. Badges, icons with text, record actions and header labels are never cut off.

`TableColumn::width()` sets a fixed width in pixels or rem, which wins over the content and takes no spare room. A child column also takes any other CSS width, such as `30%` of the child table. The arrow, record action and record link columns size themselves. Each open child table sizes its own columns, from its header and its own rows, in the width the open row leaves it; the same rules apply, and `width()` on a child `TableColumn` wins there too. If a child table doesn't fit even at its narrowest, the parent columns widen to make room and the grid scrolls sideways.

The browser measures again when the window or the grid resizes, the page, search, filters or data change, or a row opens.

### Row height

Each parent and child row grows to fit its tallest cell, so text can wrap and an entry's `belowContent()`, such as a code badge under a product name, shows in full. A row is never shorter than 44 pixels, and the cells stay centred vertically:

```php
use Filament\Schemas\Components\Text;

TextEntry::make('product')
    ->belowContent(fn (?OrderLine $record): ?Text => filled($record?->code)
        ? Text::make($record->code)->badge()->color('gray')
        : null),
```

The browser measures each row once, at the current column widths, and measures again when the rows or the column widths change. To skip measuring, give every parent row the same height with `rowHeight(56)`, or pick it per row from the row's state, returning `null` to measure that row:

```php
DataGridEntry::make('deliveries')
    ->rowHeight(fn (array $state): ?int => count($state['holds']) > 2 ? 72 : null)
```

Child rows are always measured. A fixed height doesn't make the content fit, so content taller than the row is cut off.

### Heading, description and icon

Like a section, the grid can have a heading, a description and an icon above it:

```php
DataGridEntry::make('deliveries')
    ->heading('Deliveries')
    ->description('Where each order on the route stands.')
    ->icon(Heroicon::OutlinedTruck)
```

By default the header looks like a Filament table's header, which has the same spacing as a regular section. Add `compact()` to match a compact section (`Section::make()->compact()`): the grid then uses Filament's own section header markup, so it has the same padding, icon position, border and corners, and follows your theme:

```php
DataGridEntry::make('deliveries')
    ->heading('Deliveries')
    ->icon(Heroicon::OutlinedTruck)
    ->compact()
```

The columns line up with the header the way a Filament table's do. The first column (or the chevron, when rows open) starts on the header icon's edge, and a first column after the chevron starts on the heading's edge. The last column (or the record link chevron) ends the same distance from the right. That distance is 24px on screens `sm` and wider and 16px below, like a Filament table, and always 16px with `compact()`, like a compact section.

### Filters

`filters()` takes Filament's own table filters. They appear behind the filter button next to the search field, with the same form, "Apply filters" and "Reset" as a Filament table:

```php
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

DataGridEntry::make('deliveries')
    ->filters([
        SelectFilter::make('fill_status')->options(['complete' => 'Complete', 'partial' => 'Partial']),
        SelectFilter::make('customer')->multiple()->options($customers),
        TernaryFilter::make('is_late'),
    ])
```

The grid applies `SelectFilter` and `TernaryFilter` itself, by comparing each row's `attribute()` (the filter's name by default) with the chosen value. Any other filter, such as a plain `Filter` with a custom query, needs `records()`. As with Filament's custom data tables, `records()` receives the filter state as `$filters`, for example `['fill_status' => ['value' => 'partial'], 'customer' => ['values' => ['Ana']]]`, and applies it itself.

### Totals

`totals()` adds a footer row under the grid with the sum of the columns you list. Each `Total` names an entry of `schema()` and sums that entry's state, before `formatStateUsing()`. A blank or non-numeric state counts as 0. Columns without a total stay empty, and `totalsLabel()` takes the first column when that column has no total of its own. The label's cell spans every column before the first total. It follows the first column's alignment, or with `totalsLabelAlignment(Alignment::End)` it ends right next to the first total:

```php
use Waguilar\FilamentDataGrid\Totals\Total;

DataGridEntry::make('receivables')
    ->totals([
        Total::make('total')->money('GTQ', 'es_GT'),
        Total::make('paid')->money('GTQ', 'es_GT'),
        Total::make('owed')->money('GTQ', 'es_GT'),
    ])
    ->totalsLabel('Total')
```

The browser adds up the totals, so they change with the search and the filters. They cover every row that matches, not only the page you see. The footer hides while no row matches. In `toTableContent()` the grid only holds the table's current page, so the totals cover that page. `serverSide()` grids can't have totals, because the browser only holds one page. Show totals you work out on the server in `description()` instead.

Format a total like its column. The options match Filament's own:

| Method | Formats like |
| --- | --- |
| `money($currency, $locale = null, $decimalPlaces = null)` | `TextEntry::money()` and Laravel's `Number::currency()` |
| `numeric($decimalPlaces = null, $decimalSeparator, $thousandsSeparator, $maxDecimalPlaces = null, $locale = null)` | `TextEntry::numeric()`. With separators it formats like PHP's `number_format()`. |
| `prefix()` and `suffix()` | Text before and after the number |
| `formatUsing(RawJs::make('(value) => ...'))` | Your own JavaScript function, which gets the sum as a number |

Without a method, a total is formatted like `numeric()` in the app's locale. Every argument also takes a closure, so a format can come from tenant settings. The server reads the locale's symbols, grouping and decimals from PHP's `intl`, and the browser formats with them. So a total matches the server's formatting even where the browser's own `Intl` differs from PHP's, for example `1.234` in Spanish.

### Record actions

`recordActions()` puts Filament Actions at the end of each row, as a table's `recordActions()` does. Each action gets that row as its `$record`, so `visible()`, `authorize()`, `schema()`, `fillForm()` and `action()` work per row, and modals, slide-overs and `ActionGroup` menus work as usual:

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

DataGridEntry::make('lines')
    ->state(fn (Order $record) => $record->lines()->latest()->get())
    ->table([...])
    ->schema([...])
    ->recordActions([
        Action::make('adjust')
            ->visible(fn (OrderLine $record): bool => $record->quantity < 10)
            ->authorize(fn (OrderLine $record): bool => auth()->user()->can('update', $record))
            ->schema(fn (OrderLine $record): array => [TextInput::make('quantity')->numeric()])
            ->action(fn (array $data, OrderLine $record) => $record->update($data)),
        ActionGroup::make([...]),
    ])
```

An action without `->view()` shows as a small link and a group as a menu, like in a table, so both look the same. After an action runs, the page renders again and the grid shows the new `state()`.

- With Eloquent rows, each action finds its row again by its model key when it is clicked, so rows added or removed in the meantime don't make it act on the wrong one. If the row is gone, nothing runs. It never falls back to the page's record.
- With array rows, the row is found by its array key, so give the rows stable keys (`['tea' => [...], 'salt' => [...]]`, not a list that can shift). `$record` is the row's array plus Filament's `__key`, as in a table with array records.
- It works in browser mode only. `serverSide()` throws `UnsupportedRecordActions`, because the action finds its row among the entry's state. Inside a table (`toTableContent()`), put the actions on the table's `recordActions()` instead; combining both throws too.

To test an action, call it as a schema component action with the row's key (the model key, or the array key) as the `row` argument:

```php
use Filament\Actions\Testing\TestAction;

livewire(ViewOrder::class, ['record' => $order->getKey()])
    ->callAction(
        TestAction::make('adjust')->schemaComponent('lines', schema: 'infolist')->arguments(['row' => $line->getKey()]),
        data: ['quantity' => 7],
    )
    ->assertHasNoActionErrors();
```

`assertActionVisible()`, `assertActionHidden()`, `mountAction()` and `fillForm()` with a fake `FileUpload` work as usual. `'lines'` is the entry's key, which is its name unless a parent sets a key: check the `schemaComponent` value in the rendered button's `wire:click` if unsure.

How it differs from a table's record actions:

- The grid sets each action's `record()` and `bootUsing()` itself and reserves the `row` argument, so don't set those on the actions you pass in.
- A click on a row that has gone away closes quietly. Filament's test helpers resolve the action outside that guard, so `callAction()` or `assertActionVisible()` for a missing row throws `ActionNotResolvableException` instead.
- `$livewire` is the page that holds the entry. There is no `$table`, and on a page that replaces a relation manager there is no `getOwnerRecord()`: use the page's `$record`.
- There are no bulk actions, `recordUrl()`, clickable rows, or settings for the actions column's position, label or alignment.
- Filament's prebuilt actions such as `EditAction` and `DeleteAction` expect a table or a resource record. Build your own `Action`s for the grid.

#### Actions on child rows

`childRecordActions()` puts actions at the end of each child row of the nested `RepeatableEntry` (the one with `table()`). They work like `recordActions()`: modals, slide-overs, `fillForm()`, `schema()` and groups. `$record` is the child row, a model or its array plus `__key`, the child's array key.

The entry also works inside a form. Its `state()` can read the form with `Get`, and an action can rewrite the form with `Set`. The grid then renders again from the new state. For example, payments kept as unsaved form state keyed by uuid, shown one group per method:

```php
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

Hidden::make('payments'),

DataGridEntry::make('payments_grid')
    ->state(fn (Get $get): array => groupByMethod($get('payments') ?? []))
    ->table([TableColumn::make('Method'), TableColumn::make('Total')->alignEnd()])
    ->schema([
        TextEntry::make('method'),
        TextEntry::make('total')->money('GTQ'),
        RepeatableEntry::make('payments')
            ->table([TableColumn::make('Amount')->alignEnd()])
            ->schema([TextEntry::make('amount')->money('GTQ')]),
    ])
    ->childRecordActions([
        Action::make('editPayment')
            ->slideOver()
            ->fillForm(fn (array $record): array => ['amount' => $record['amount']])
            ->schema([TextInput::make('amount')->numeric()->required()])
            ->action(fn (array $data, array $record, Get $get, Set $set) => $set(
                'payments.'.$record['__key'].'.amount', $data['amount'], shouldCallUpdatedHooks: true,
            )),
        Action::make('deletePayment')
            ->action(fn (array $record, Get $get, Set $set) => $set(
                'payments', array_diff_key($get('payments'), [$record['__key'] => true]), shouldCallUpdatedHooks: true,
            )),
    ])
```

- Give the entry a name other than the field it reads (`payments_grid`, not `payments`).
- Key the groups and the children by stable keys, like a method id and the payment uuid. An action finds its parent row by `row` and its child by `child`, and the open groups stay open by their keys.
- `shouldCallUpdatedHooks: true` runs the field's `afterStateUpdated()`, so totals elsewhere on the page that react to it update too.
- For the parent row, the entry is injected as `$component`: `$component->resolveRecordActionRecord($arguments['row'])`.
- A child action can't share a name with a parent action, because Filament finds actions by name. That throws `DuplicateRecordActionName`.
- It throws `UnsupportedRecordActions` with `serverSide()`, `toTableContent()` or `childTable()`, and when the schema has no nested table `RepeatableEntry`.
- To test, pass both keys: `TestAction::make('deletePayment')->schemaComponent('payments_grid', schema: 'form')->arguments(['row' => 'cash', 'child' => $uuid])`.

`recordActions()` on parent rows works inside a form the same way.

### Inside a Filament table

To let Filament handle searching, filtering and paging, build a normal Filament table and use the grid as its content. Filament keeps everything around the rows: the heading, search field, filters (in a dropdown, modal or slide-over), active filter chips, pagination, empty state and header actions. The grid draws only the current page of records, with child rows that open:

```php
use Filament\Tables\Table;
use Waguilar\FilamentDataGrid\DataGridEntry;

public function table(Table $table): Table
{
    return $table
        ->query(Delivery::query())
        ->columns([TextColumn::make('number')->searchable()])
        ->filters([SelectFilter::make('fill_status')->options([...])])
        ->filtersTriggerAction(fn (Action $action) => $action->slideOver())
        ->content(
            DataGridEntry::make('deliveries')
                ->table([...])
                ->schema([...])
                ->toTableContent(),
        );
}
```

`toTableContent()` uses Filament's `content()` hook, so the table works the same as any other Filament table, with `query()` or `records()`. Each parent row also shows the table's `recordActions()` (Filament Actions, so their modals and slide-overs work as usual) and, when the table has a `recordUrl()`, a chevron that opens the record. Clicking the row opens the record too; the arrow at the start of the row opens its child rows. It works in a relation manager, whose table already has the relationship query. Define the grid's columns with `table()` and `schema()` as above. The table's `columns()` still decide what can be searched and sorted, but aren't shown. Leave `searchable()`, `filters()` and `paginated()` off the grid itself here, because the table does that work.

To give the table's heading an icon, like a section's, pass the heading through `IconHeading`. It works on any Filament table, with or without the grid:

```php
use Waguilar\FilamentDataGrid\Support\IconHeading;

$table
    ->heading(IconHeading::make('Deliveries', Heroicon::OutlinedTruck))
    ->description('Where each order on the route stands.')
```

Inside a table, the heading, description and icon belong to the table, because Filament draws the table's header (with its search, filters and header actions) before it draws the content. The grid's own `heading()`, `description()` and `icon()` aren't shown there. `compact()` still works: on the grid, it makes the table's header compact too, so it lines up with compact sections:

```php
$table
    ->heading(IconHeading::make('Deliveries', Heroicon::OutlinedTruck))
    ->description('Where each order on the route stands.')
    ->content(DataGridEntry::make('deliveries')->compact()->...->toTableContent())
```

Cells are Filament infolist entries, so their own options work in the grid. For example, a `TextEntry` with `badge()` and an array state shows one badge per item, and an `IconEntry` with `tooltip()` makes an icon-only column:

```php
IconEntry::make('kind')
    ->icon(fn (string $state) => $state === 'pickup' ? Heroicon::OutlinedHandRaised : Heroicon::OutlinedTruck)
    ->tooltip(fn (string $state) => ucfirst($state)),
TextEntry::make('holds')->badge(),
```

An entry with `url()` is a link. Like a table column's link, its text underlines on hover and keyboard focus; its icons and badges don't.

To use the same child table on its own elsewhere, build its `table()` columns and `schema()` entries in a method of your own that returns new instances on every call, and pass them both to a nested `RepeatableEntry` and to a standalone `DataGridEntry`.

### Searching, filtering and paging it yourself

By default, the server sends every row with the page, and search and pagination run in the browser. Results are instant and nothing goes back to the server, which suits grids of up to a few hundred parent rows whose size you know.

For larger or unknown sizes, make the entry server-side. The page then loads only the first page of rows, and each search, page or page size change asks the server for the rows it needs:

```php
DataGridEntry::make('deliveries')
    ->serverSide()
    ->state(fn (): array => $deliveries)
```

With only `state()`, the server still reads every row on each request (a `state()` closure runs once per request, and again for each render), but renders and sends only the current page. To let your data source do the work, as with Filament's [custom data tables](https://filamentphp.com/docs/5.x/tables/custom-data), use `records()`. It receives the search, the page and the page size (`null` for "All"), and returns a `LengthAwarePaginator`, or an array or collection that the grid pages for you:

```php
use Illuminate\Pagination\LengthAwarePaginator;

DataGridEntry::make('deliveries')
    ->serverSide()
    ->records(fn (?string $search, int $page, ?int $recordsPerPage): LengthAwarePaginator => Delivery::query()
        ->when($search, fn ($query) => $query->where('number', 'like', "%{$search}%"))
        ->paginate($recordsPerPage ?? PHP_INT_MAX, page: $page))
```

Search terms and page numbers come from the browser. As with Filament's custom data, `records()` must return only data the current user may see.

To make every grid in your app server-side, set it once in a service provider. A grid can still opt out with `serverSide(false)`:

```php
DataGridEntry::configureUsing(fn (DataGridEntry $entry) => $entry->serverSide());
```

## Validation

Validation runs on the server through Filament, using `Repeater`'s keys (`data.lines.{uuid}.quantity`). Invalid cells are outlined in red and show their message on hover. A summary below the grid lists each error by row and column:

```text
Row 2 · Quantity: The quantity field is required.
```

Number and date cells also check their limits in the browser as you type, before any request is sent.

## Translations

The plugin ships English and Spanish translations. To change them, publish the files:

```bash
php artisan vendor:publish --tag=filament-data-grid-translations
```

Server-side validation messages come from Laravel's `lang/{locale}/validation.php`, as with any Filament field.

## Styling

Every element uses `fi-data-grid-*` hook classes and Filament's color variables, so the grid follows your panel's colors and dark mode. The grid's own colors are CSS variables that you can override in your theme:

```css
.fi-data-grid {
    --fi-data-grid-hint: var(--gray-500);
    --fi-data-grid-hint-active: var(--primary-700);
}

.dark .fi-data-grid {
    --fi-data-grid-hint: var(--gray-400);
}
```

The variables for editable cells are `--fi-data-grid-hint` (the dashed underline), `--fi-data-grid-hint-active` (the solid underline, the header pencil, a hovered icon and the keyboard outline), `--fi-data-grid-hover-bg`, `--fi-data-grid-highlight-bg` (`highlightEditableColumns()`), `--fi-data-grid-cell-icon`, `--fi-data-grid-editable-bg` and `--fi-data-grid-readonly-bg`.

## Icons

Every icon the plugin draws goes through Filament's [icon aliases](https://filamentphp.com/docs/5.x/styling/icons#replacing-the-default-icons), so you can replace them in a service provider like any Filament icon:

```php
use Filament\Support\Facades\FilamentIcon;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

FilamentIcon::register([
    DataGridIconAlias::ROW_EXPAND => 'fal-sharp-chevron-right',
]);
```

| Alias | Icon |
| --- | --- |
| `DataGridIconAlias::ROW_EXPAND` (`filament-data-grid::row.expand`) | The arrow that opens a row's child rows. |
| `DataGridIconAlias::ROW_OPEN` (`filament-data-grid::row.open`) | The chevron that opens a record, inside a Filament table. |
| `DataGridIconAlias::ROW_ACTIONS` (`filament-data-grid::row.actions`) | The row menu of the editable grid. |
| `DataGridIconAlias::CELL_REORDER` (`filament-data-grid::cell.reorder`) | The drag handle. |
| `DataGridIconAlias::CELL_DATE` (`filament-data-grid::cell.date`) | The calendar in date cells. |
| `DataGridIconAlias::CELL_EDIT` (`filament-data-grid::cell.edit`) | The pencil in cells that open a modal, and after the header label of editable columns. |
| `DataGridIconAlias::CELL_STEP_UP` (`filament-data-grid::cell.step-up`) | The up button in an open number editor. |
| `DataGridIconAlias::CELL_STEP_DOWN` (`filament-data-grid::cell.step-down`) | The down button in an open number editor. |

The search, filter and pagination icons are Filament's own, under its table and pagination aliases.

The row chevrons line up with the header icon by what they draw, not by their box. Icons have different empty space around the shape inside their box, so the grid fits each chevron's box to its shape and aligns that shape with the header icon's. An open row's chevron, turned to point down, is centered on the row instead. This works with whatever icon you register.

## How it works

The grid is drawn with [SlickGrid-Universal](https://github.com/ghiscoding/slickgrid-universal) inside a `wire:ignore` element. SlickGrid-Universal is bundled with the plugin, so your app doesn't install it.

Child tables from `childTable()` aren't inside the grid. Livewire renders them in a layer beside it, and the grid leaves an empty row of the same height where each open one sits.

The server renders the rows, the cell descriptions and the row actions, and sends them to the browser as JSON. Every edit is written back to the field's state through Livewire.

## Limitations

- The whole grid is sent again on every update. 200 rows take about 47 ms on the server and 11 KB gzipped, which is fine for grids the size of an order. Grids with thousands of rows aren't a good fit yet.
- Totals are for `DataGridEntry` only. The editable `DataGrid` and child rows from a nested `RepeatableEntry` don't have them yet. A `childTable()` that is itself a `DataGridEntry` can have its own.
- The compiled JavaScript is about 88 KB gzipped for `DataGrid` and 57 KB for `DataGridEntry`, most of it SlickGrid-Universal's grid. It loads only on pages with a grid.
- The select dropdown is Filament's own JavaScript, copied into the plugin's build from Filament 5. The same build runs on Filament 4. Check the grid after upgrading Filament.

## Testing

```bash
composer test
```

The browser tests use [Pest's browser plugin](https://pestphp.com/docs/browser-testing). Install Chromium once with `npx playwright install chromium`.

The grid's payload keeps accented and other non-ASCII characters as they are, so Livewire's `assertSee('Árbol natural')` matches the text of a cell.

To try the grid in a demo panel:

```bash
composer install
npm install
composer serve
```

Then open http://127.0.0.1:8000/demo/data-grid-demo.

After changing any JavaScript or CSS, run `npm run build` and commit `resources/dist`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [William Aguilar](https://github.com/Waguilar33)
- [All Contributors](../../contributors)

The compiled assets include [SlickGrid-Universal](https://github.com/ghiscoding/slickgrid-universal), [Floating UI](https://github.com/floating-ui/floating-ui), [SortableJS](https://github.com/SortableJS/Sortable) and the select component from [Filament](https://github.com/filamentphp/filament), all MIT licensed. Their license notices are in [`resources/dist/THIRD-PARTY-LICENSES.txt`](resources/dist/THIRD-PARTY-LICENSES.txt).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
