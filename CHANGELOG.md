# Changelog

All notable changes to `filament-data-grid` will be documented in this file.

## Unreleased

First preview. Not ready for production; the API may change before v1.0.0.

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
