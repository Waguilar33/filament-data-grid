# Changelog

All notable changes to `filament-data-grid` will be documented in this file.

## Unreleased

First preview. Not ready for production; the API may change before v1.0.0.

- `DataGrid` form field that extends `Repeater`, with Filament form components as cells and infolist entries as read-only cells.
- Text, number, select, radio, toggle buttons, toggle, checkbox and date cells edited in place; any other field opens in a modal.
- Keyboard entry: typing starts editing, Enter and Shift+Enter save and move down or up, and Tab moves across.
- A trailing empty row, which doesn't count toward `minItems()` or `maxItems()`.
- Frozen columns, drag reordering, row menu with extra item actions, and per-row validation errors.
- The JavaScript loads only on pages that use the field. The stylesheet loads with every Filament page, so the page doesn't shift when a grid appears.
- English and Spanish translations.
- `DataGridEntry`: a read-only grid whose rows open to show child rows, with search and pagination over parent rows.
- Supports Filament 4 and 5, Laravel 11 to 13 and PHP 8.2+.
