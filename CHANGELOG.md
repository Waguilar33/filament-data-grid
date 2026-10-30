# Changelog

All notable changes to `filament-data-grid` will be documented in this file.

## v1.0.0-beta.6 - 2026-10-30

- On a page with a record, such as a View page, `DataGridEntry` rows given as plain arrays now reach `childTable()`, `expandedAbove()`, `expandedBelow()`, `rowHeight()` and the filters as the row itself. Before, they got the page's record instead.

## v1.0.0-beta.5 - 2026-10-30

- `DataGridEntry::childTable()` opens a full Filament table under each parent row, scoped to that row. It's a Livewire component that extends the new `ChildTable` base class, so it has its own search, filters, pagination with a per-page select, bulk selection and bulk actions, and record actions, and its columns can differ per parent through `visible()`. The parent row resizes as the child table pages or filters. It works in a standalone grid and in `toTableContent()`, but not with `serverSide()`.

## v1.0.0-beta.4 - 2026-10-30

- A record action dropdown, such as an `ActionGroup` on a parent row, now opens above an open row's panel below it instead of behind it.
- `DataGridEntry` columns now fit their content, like an HTML table: short columns such as an icon or a date stay narrow, and long text gets the spare room. Badges, icons with text and the table's record actions are never cut off, and the grid scrolls sideways only when the columns can't fit even with their text wrapped. Each open child table sizes its columns to its own rows. `TableColumn::width()` still sets a fixed width.

## v1.0.0-beta.3 - 2026-10-30

- A single badge in a grid cell, such as a `TextEntry` badge with an icon, is now centred vertically. Filament lays it out on a text line, which left more space below it than above.

## v1.0.0-beta.2 - 2026-10-30

- `expandedAbove()` and `expandedBelow()` show your own Filament components above and below the child table of an open `DataGridEntry` row.
- Every plugin icon can be replaced through Filament's icon aliases (`DataGridIconAlias`).
- `DataGridEntry` rows, parent and child, grow to fit their content, so wrapped text, several badges and an entry's `belowContent()` are no longer cut off. `rowHeight()` sets a fixed height for every parent row, or one per row from a closure.
- Inside a Filament table, each parent row shows the table's `recordActions()` and, with `recordUrl()`, a chevron and a clickable row that open the record.

## v1.0.0-beta.1 - 2026-10-30

First beta. You can install and use it, but the API may change before v1.0.0.

- `DataGrid` form field that extends `Repeater`, with Filament form components as cells and infolist entries as read-only cells.
- Text, number, select, radio, toggle buttons, toggle, checkbox and date cells edited in place; any other field opens in a modal.
- Keyboard entry: typing starts editing, Enter and Shift+Enter save and move down or up, and Tab moves across.
- A trailing empty row, which doesn't count toward `minItems()` or `maxItems()`.
- Frozen columns, drag reordering, row menu with extra item actions, and per-row validation errors.
- The JavaScript is about 88 KB gzipped for `DataGrid` and 57 KB for `DataGridEntry`, and loads only on pages that use the field. The stylesheet loads with every Filament page, so the page doesn't shift when a grid appears.
- English and Spanish translations.
- `DataGridEntry`: a read-only grid whose rows open to show child rows, inside a Filament table card with an optional heading, description and icon. Search, Filament filters and Filament's pagination work over parent rows, in the browser or on the server. `toTableContent()` draws the grid inside a native Filament table, which then does the searching, filtering and paging.
- `childTablePadding()` sets the space around a child table (15 pixels by default); every child row keeps the full row height.
- Editable child rows in `DataGrid`: a nested `DataGrid` in the schema becomes the editable table a row opens to, chosen per row with `visible()`, with nested state paths and validation errors. `expandRowsWhen()` opens rows from their state, and `childTablePadding()` works as in `DataGridEntry`.
- `IconHeading` gives a Filament table's heading an icon, like a section's.
- Supports Filament 4 and 5, Laravel 11 to 13 and PHP 8.2+.
