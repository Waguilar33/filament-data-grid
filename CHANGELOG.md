# Changelog

All notable changes to `filament-data-grid` will be documented in this file.

## v1.0.0-beta.18 - 2026-10-31

- A `DataGridEntry` whose `state()` is a closure, for example a query, now evaluates it once per render. Before, Filament's `RepeatableEntry` checked its cached rows against a fresh `getState()` on every child schema lookup. A query returns new model instances, so the check always failed, and one render ran the closure 17 times for a single row. A later render, such as after an action, still reads the state again.

## v1.0.0-beta.17 - 2026-10-31

- The totals label's cell now spans every column before the first total, so `totalsLabelAlignment(Alignment::End)` puts the label right next to the first total. With the default start alignment the label still starts at the first column's edge.

## v1.0.0-beta.16 - 2026-10-31

- `DataGridEntry::totalsLabelAlignment()` aligns the totals label in its cell, for example `Alignment::End`. By default the label follows its column's alignment.

## v1.0.0-beta.15 - 2026-10-31

- `DataGridEntry::totals()` adds a footer row with the sum of the columns you list, each a `Total::make('field')`. Each total is lined up and aligned with its column, and `totalsLabel()` goes in the first column. The browser adds up the totals, so they follow the search and filters and cover every matching row, not just the page. The footer hides while no row matches. In `toTableContent()` they cover the table's page. `serverSide()` grids throw `UnsupportedTotals`.
- A `Total` is formatted like its column, with `money()`, `numeric()`, `prefix()`, `suffix()` or a `RawJs` `formatUsing()`. The server reads the locale's symbols, grouping and decimals from PHP `intl`, so a total matches `Number::currency()` and `Number::format()` even where the browser's `Intl` differs.

## v1.0.0-beta.14 - 2026-10-31

- A grid cell whose entry has `url()` now underlines its text on hover and keyboard focus, like a link in a Filament table column. Its icons and badges stay without underline. Before, Filament's infolist link showed only its color.

## v1.0.0-beta.13 - 2026-10-31

- A selected cell, for example one left with Escape after editing, now draws its whole 1px outline in every row. Before, its bottom edge was hidden: under the next row, which overlaps it by 1px, or, in the last row, by the grid's rounded frame. The outline is now drawn inside the cell, and the selected cell sits above its neighbours.
- An editable grid leaves out a column whose field is hidden in every row, for example `visible(false)` or a closure that is false for all rows. A column shown in at least one row stays, with empty cells where it is hidden. With no rows, the column follows the field's visibility in the row template.

## v1.0.0-beta.12 - 2026-10-31

- Editable grid cells no longer look like input boxes. The value of an editable cell is underlined with a dashed line, which turns solid in the primary color on hover or keyboard selection while the cell lightens slightly. The cell is outlined only while its editor is open; a keyboard-selected cell keeps a 1px outline. An empty editable cell shows its `placeholder()`, or a short underline. Editable and read-only cells now share one background (`--fi-data-grid-editable-bg` and `--fi-data-grid-readonly-bg` default to transparent), and the 1px hover ring is gone.
- A pencil (`CELL_EDIT` alias) follows the header label of every column that is editable in at least one row. It updates on live changes without closing an open editor.
- `DataGrid::highlightEditableColumns(bool|array|Closure $columns = true)` tints the editable cells of every column, or of the listed fields. Off by default.
- A field's `suffixIcon()` is shown after the value of its editable cells. Date and modal cells show it in place of their own icon.
- An open number editor has up and down buttons that step like the arrow keys, including masks, and repeat while held. New aliases `CELL_STEP_UP` and `CELL_STEP_DOWN`.
- A `readOnly()` `TextInput` or date field is locked like a disabled one and no longer opens an editor.
- Locked cells hide their icons, including the calendar of a disabled date cell.
- An open row's chevron is now centered on its row. Before, its glyph-fitted box was rotated around the box's center, so the drawn chevron sat up to about 5px high.

## v1.0.0-beta.11 - 2026-10-31

- ArrowUp and ArrowDown step a numeric `TextInput` cell while you edit it, like a native number input, with or without a mask. The step is `step()` when it is a number, otherwise 1 (so `numeric()`'s default `'any'` steps by 1). A blank cell starts from 0, the result stays within `minValue()` and `maxValue()`, and an explicit step snaps to the step from `minValue()`. The arrows no longer move to another row while the editor is open. A masked cell keeps its mask. Arrows with Shift, Alt, Ctrl or Meta are left alone.
- A grid with a header (editable `DataGrid` or `compact()` `DataGridEntry`) now shrinks back when its container gets narrower, for example after the sidebar opens again. Before, it kept its widest width and its last columns were cut off past the card. The grid root's single column is now `minmax(0, 1fr)`, so the grid's own content no longer holds it wide.

## v1.0.0-beta.10 - 2026-10-31

- Editable `DataGrid` rows, parent and child, grow to fit their tallest cell (44px minimum), so `belowContent()` and wrapped text are no longer cut off. They are measured again on state changes, when rows open or close, and on resize.
- The editable `DataGrid` has `heading()`, `description()`, `icon()` and `compact()`, drawn as Filament's section header around the grid, with the same column alignment as `DataGridEntry`.
- Row chevrons (`ROW_EXPAND`, `ROW_OPEN`) line up by their drawn shape, whatever icon is registered: each chevron's box is fitted to its shape, and the shape starts where the header icon's shape starts.
- The grid payload keeps non-ASCII characters unescaped, so `assertSee()` matches accented cell text.
- `DataGridEntry` grids without `compact()` now line their columns up with their header like a Filament table: the first column, or the chevron, starts on the header icon's edge (24px from `sm` up, 16px below), a first column after the chevron starts on the heading's edge, and the last column, or the record link chevron, ends the same distance from the right. Before, the edge columns used 12px cell padding. This works standalone and in `toTableContent()`.
- A numeric `TextInput` cell now uses its own `mask()` and `stripCharacters()`. The editor masks what you type or paste with Filament's Alpine mask, the cell shows the masked text, and a commit stores the number with the strip characters removed, so `minValue()` and `maxValue()` still check the number. Cells without a mask work as before.

## v1.0.0-beta.9 - 2026-10-31

- A `compact()` grid now lines its columns up with the compact header above them, standalone and in `toTableContent()`. The first column, or the chevron of rows that open, starts on the header icon's edge (16px). After a chevron, the first column starts on the heading's edge (52px), and the last column ends 16px from the right. Before, they sat 4px to the left.

## v1.0.0-beta.8 - 2026-10-31

- `DataGridEntry::compact()` draws the grid's heading, description and icon as a compact Filament section header, with Filament's own section markup, so it lines up with `Section::make()->compact()` cards. Without it, the header keeps the table look. In `toTableContent()`, `compact()` makes the table's own header compact; give that header an icon with `IconHeading`.

## v1.0.0-beta.7 - 2026-10-31

- `DataGridEntry::recordActions()` adds Filament Actions and `ActionGroup` menus to each row of a standalone grid, with that row as `$record` for `visible()`, `authorize()`, `schema()` and `action()`. They look and size like a table's record actions. Eloquent rows are found again by model key and array rows by array key, so an action never runs on a different row or on the page's record. Test them with `TestAction::make('name')->schemaComponent('entry', schema: 'infolist')->arguments(['row' => $key])`. Not available with `serverSide()` or `toTableContent()` (`UnsupportedRecordActions`).

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
