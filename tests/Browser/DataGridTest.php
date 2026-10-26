<?php

declare(strict_types=1);

const LINES = '[id="form.lines"]';

const ADJUSTMENTS = '[id="form.adjustments"]';

function cell(int $row, int $column, string $grid = LINES): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"] .slick-cell.l%d', $grid, $row * 44, $column);
}

function savedState(string $grid = 'lines'): string
{
    return sprintf('JSON.parse(document.querySelector("[data-saved-state]").textContent).%s', $grid);
}

function dragRow(string $handle, string $target): string
{
    return <<<JS
        () => {
            const handle = document.querySelector('{$handle}')
            const target = document.querySelector('{$target}').getBoundingClientRect()
            const start = handle.getBoundingClientRect()
            const at = (y) => ({ bubbles: true, cancelable: true, button: 0, buttons: 1, clientX: start.x + 4, clientY: y })
            const endY = target.y + target.height - 4

            handle.dispatchEvent(new MouseEvent('mousedown', at(start.y + 4)))

            for (let step = 1; step <= 10; step++) {
                const y = start.y + 4 + ((endY - start.y - 4) * step) / 10

                document.elementFromPoint(start.x + 4, y).dispatchEvent(new MouseEvent('mousemove', at(y)))
            }

            window.dispatchEvent(new MouseEvent('mouseup', at(endY)))
        }
        JS;
}

function openDropdownSearch(): string
{
    return '.fi-dropdown-panel:not([style*="display: none"]) .fi-select-input-search-ctn input';
}

it('picks a product with the Filament select, recalculates the row and appends a blank row', function (): void {
    $page = visit('/demo/data-grid-demo')
        ->assertSee('Creamy Coffee 100 g')
        ->assertPresent(cell(0, 0).' .fi-select-input-btn')
        ->click(cell(2, 0).' .fi-select-input-btn')
        ->typeSlowly(openDropdownSearch(), 'honey', 30)
        ->wait(1.6)
        ->keys(openDropdownSearch(), ['ArrowDown', 'Enter'])
        ->wait(1.5);

    $page->assertSeeIn(cell(2, 0).' .fi-select-input-value-label', 'Honey')
        ->assertScript('document.activeElement.matches(".fi-data-grid-editor-number")')
        ->assertSeeIn(cell(2, 6), 'GTQ')
        ->assertPresent(cell(3, 0))
        ->assertNoJavaScriptErrors();
});

it('moves down with Enter and up with Shift+Enter while typing', function (): void {
    visit('/demo/data-grid-demo')
        ->click(cell(0, 1))
        ->keys(cell(0, 1).' input', ['3', 'Enter'])
        ->assertScript('document.activeElement.closest(".slick-row").style.top', '44px')
        ->keys(cell(1, 1).' input', ['4', 'Shift+Enter'])
        ->assertScript('document.activeElement.closest(".slick-row").style.top', '0px')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState().'[0].quantity', 3)
        ->assertScript(savedState().'[1].quantity', 4);
});

it('opens a select cell from the keyboard and moves on after picking', function (): void {
    visit('/demo/data-grid-demo')
        ->click(cell(0, 1))
        ->keys(cell(0, 1).' input', 'Escape')
        ->keys(LINES.' .slick-cell.active', ['ArrowRight', 'Enter'])
        ->assertVisible('.fi-dropdown-panel:not([style*="display: none"])')
        ->keys('.fi-dropdown-panel:not([style*="display: none"])', ['k', 'Enter'])
        ->wait(0.5)
        ->assertSeeIn(cell(0, 2).' .fi-select-input-value-label', 'Kilogram')
        ->assertScript('document.querySelector(".slick-cell.active").classList.contains("l3")');
});

it('saves a value typed into a cell without pressing Enter', function (): void {
    visit('/demo/data-grid-demo')
        ->click(cell(0, 1))
        ->typeSlowly(cell(0, 1).' input', '7', 30)
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState().'[0].quantity', 7)
        ->assertScript(savedState().'[0].note', 'seeded')
        ->assertScript(savedState().'.length', 2);
});

it('shows a server error on the cell of its row and keeps it after deleting another row', function (): void {
    $page = visit('/demo/data-grid-demo')
        ->click(cell(1, 1))
        ->keys(cell(1, 1).' input', 'Backspace')
        ->click('Save')
        ->wait(1.5);

    $page->assertSee('Row 2 · Quantity: The quantity field is required.')
        ->assertAttributeContains(cell(1, 1), 'class', 'fi-data-grid-cell-invalid')
        ->click(cell(0, 7).' .fi-dropdown-trigger button')
        ->click('Delete')
        ->wait(1.5)
        ->assertSee('Row 1 · Quantity: The quantity field is required.')
        ->assertAttributeContains(cell(0, 1), 'class', 'fi-data-grid-cell-invalid');
});

it('opens row actions from the row menu', function (): void {
    visit('/demo/data-grid-demo')
        ->click(cell(0, 7).' .fi-dropdown-trigger button')
        ->click('Stock')
        ->wait(1)
        ->assertSee('Creamy Coffee 100 g')
        ->assertSee('Available');
});

it('searches inside a slide-over without closing it on Escape', function (): void {
    $slideOver = '.fi-modal-window [id$=".lines"]';

    visit('/demo/data-grid-demo')
        ->click('Edit in a slide-over')
        ->wait(1)
        ->click(cell(1, 0, $slideOver).' .fi-select-input-btn')
        ->typeSlowly(openDropdownSearch(), 'tea', 30)
        ->wait(1.6)
        ->keys(openDropdownSearch(), 'Escape')
        ->assertVisible('.fi-modal-window')
        ->click(cell(1, 0, $slideOver).' .fi-select-input-btn')
        ->typeSlowly(openDropdownSearch(), 'tea', 30)
        ->wait(1.6)
        ->keys(openDropdownSearch(), ['ArrowDown', 'Enter'])
        ->wait(1.5)
        ->assertSeeIn(cell(1, 0, $slideOver).' .fi-select-input-value-label', 'Tea')
        ->keys(cell(1, 1, $slideOver).' input', 'Escape')
        ->wait(0.5)
        ->assertVisible('.fi-modal-window')
        ->assertPresent(cell(2, 0, $slideOver))
        ->assertNoJavaScriptErrors();
});

it('adds rows with the add action and accepts rows pushed by the server', function (): void {
    visit('/demo/data-grid-demo')
        ->click('Add adjustment')
        ->wait(1)
        ->assertPresent(cell(2, 1, ADJUSTMENTS))
        ->click('Add sample lines')
        ->wait(1)
        ->assertSee('Crispy Chocolate 100 g')
        ->assertPresent(cell(4, 0));
});

it('reorders rows by dragging the handle', function (): void {
    $page = visit('/demo/data-grid-demo');

    $page->script(dragRow(cell(0, 0, ADJUSTMENTS).' .fi-data-grid-move-handle', cell(1, 0, ADJUSTMENTS)));

    $page->wait(1)
        ->assertSeeIn(cell(0, 1, ADJUSTMENTS), 'Late delivery')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('adjustments').'[0].reason', 'Late delivery');
});

it('edits a date, a radio and a field without an inline editor', function (): void {
    visit('/demo/data-grid-demo')
        ->click(cell(1, 4, ADJUSTMENTS))
        ->type(cell(1, 4, ADJUSTMENTS).' input', '2026-11-15')
        ->keys(cell(1, 4, ADJUSTMENTS).' input', 'Enter')
        ->click(cell(0, 2, ADJUSTMENTS).' .fi-select-input-btn')
        ->click('.fi-dropdown-panel:not([style*="display: none"]) [role="option"]:nth-child(2)')
        ->click(cell(1, 5, ADJUSTMENTS))
        ->wait(1)
        ->assertSee('Color')
        ->type('.fi-modal-window input', '#123456')
        ->click('.fi-modal-window .fi-modal-heading')
        ->press('Submit')
        ->wait(1)
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('adjustments').'[1].date', '2026-11-15')
        ->assertScript(savedState('adjustments').'[0].kind', 'debit')
        ->assertScript(savedState('adjustments').'[1].color', '#123456');
});

it('keeps frozen columns in place while the rest scrolls sideways', function (): void {
    visit('/demo/data-grid-demo')
        ->assertSeeIn(LINES.' .slick-pane-left .slick-header', 'Product')
        ->assertDontSeeIn(LINES.' .slick-pane-left .slick-header', 'Quantity')
        ->assertSeeIn(LINES.' .slick-pane-right .slick-header', 'Quantity');
});

it('shows the grid in Spanish', function (): void {
    visit('/demo/data-grid-demo?locale=es')
        ->assertSee('Producto')
        ->assertSee('Seleccione una opción')
        ->assertNoJavaScriptErrors();
});
