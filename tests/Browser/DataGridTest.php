<?php

declare(strict_types=1);

const ADJUSTMENTS = '[id="form.adjustments"]';

const SCANS = '[id="form.scans"]';

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

function searchResultsFor(string $term): string
{
    return sprintf(<<<'JS'
        (() => {
            const panel = document.querySelector('.fi-dropdown-panel:not([style*="display: none"])')
            const options = [...panel.querySelectorAll('[role="option"]')].filter((option) => option.checkVisibility())

            return !panel.querySelector('.fi-select-input-message') && options.length > 0 && options.every((option) => option.textContent.toLowerCase().includes(%s))
        })()
        JS, json_encode($term));
}

function noModalOpen(): string
{
    return '![...document.querySelectorAll(".fi-modal-window")].some((modal) => modal.checkVisibility())';
}

it('picks a product with the Filament select, recalculates the row and appends a blank row', function (): void {
    $page = visitDemo()
        ->assertSee('Creamy Coffee 100 g')
        ->assertPresent(cell(0, 0).' .fi-select-input-btn')
        ->click(cell(2, 0).' .fi-select-input-btn')
        ->typeSlowly(openDropdownSearch(), 'honey', 30)
        ->assertScript(searchResultsFor('honey'))
        ->keys(openDropdownSearch(), ['ArrowDown', 'Enter']);

    $page->assertSeeIn(cell(2, 0).' .fi-select-input-value-label', 'Honey')
        ->assertScript('document.activeElement.matches(".fi-data-grid-editor-number")')
        ->assertSeeIn(cell(2, 6), '$')
        ->assertPresent(cell(3, 0))
        ->assertNoJavaScriptErrors();
});

it('moves down with Enter and up with Shift+Enter while typing', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->keys(cell(0, 1).' input', ['3', 'Enter'])
        ->assertScript('document.activeElement.closest(".slick-row").style.top', TABLE_ROW_HEIGHT.'px')
        ->keys(cell(1, 1).' input', ['4', 'Shift+Enter'])
        ->assertScript('document.activeElement.closest(".slick-row").style.top', '0px')
        ->click('Save')
        ->assertPresent('[data-saved-state]')
        ->assertScript(savedState().'[0].quantity', 3)
        ->assertScript(savedState().'[1].quantity', 4);
});

it('skips rows without an input in the column on Enter and Shift+Enter', function (): void {
    visitDemo()
        ->click(cell(0, 1, SCANS))
        ->keys(cell(0, 1, SCANS).' input', ['A', 'Enter'])
        ->assertScript('document.querySelector(\''.SCANS.' .slick-cell.active\').closest(".slick-row").style.top', (2 * TABLE_ROW_HEIGHT).'px')
        ->keys(cell(2, 1, SCANS), ['C', 'Shift+Enter'])
        ->assertScript('document.querySelector(\''.SCANS.' .slick-cell.active\').closest(".slick-row").style.top', '0px');
});

it('stays on the row when no row below has an input in the column', function (): void {
    visitDemo()
        ->click(cell(1, 5))
        ->keys(cell(1, 5).' input', ['!', 'Enter'])
        ->assertScript('document.querySelector(\''.LINES.' .slick-cell.active\').closest(".slick-row").style.top', TABLE_ROW_HEIGHT.'px');
});

it('opens a select cell from the keyboard and moves on after picking', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->keys(cell(0, 1).' input', 'Escape')
        ->keys(LINES.' .slick-cell.active', ['ArrowRight', 'Enter'])
        ->assertVisible('.fi-dropdown-panel:not([style*="display: none"])')
        ->keys('.fi-dropdown-panel:not([style*="display: none"])', ['k', 'Enter'])
        ->assertSeeIn(cell(0, 2).' .fi-select-input-value-label', 'Kilogram')
        ->assertScript('document.querySelector(".slick-cell.active").classList.contains("l3")');
});

it('saves a value typed into a cell without pressing Enter', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->typeSlowly(cell(0, 1).' input', '7', 30)
        ->click('Save')
        ->assertPresent('[data-saved-state]')
        ->assertScript(savedState().'[0].quantity', 7)
        ->assertScript(savedState().'[0].note', 'seeded')
        ->assertScript(savedState().'.length', 2);
});

it('shows a server error on the cell of its row and keeps it after deleting another row', function (): void {
    $page = visitDemo()
        ->click(cell(1, 1))
        ->keys(cell(1, 1).' input', 'Backspace')
        ->click('Save');

    $page->assertSee('Row 2 · Quantity: The quantity field is required.')
        ->assertAttributeContains(cell(1, 1), 'class', 'fi-data-grid-cell-invalid')
        ->click(cell(0, 7).' a:has-text("Delete"), '.cell(0, 7).' button:has-text("Delete")')
        ->assertSee('Row 1 · Quantity: The quantity field is required.')
        ->assertAttributeContains(cell(0, 1), 'class', 'fi-data-grid-cell-invalid');
});

it('opens a row action shown on the row', function (): void {
    visitDemo()
        ->click(cell(0, 7).' a:has-text("Stock"), '.cell(0, 7).' button:has-text("Stock")')
        ->assertVisible('.fi-modal-window')
        ->assertSee('Creamy Coffee 100 g')
        ->assertSee('Available');
});

it('searches inside a slide-over without closing it on Escape', function (): void {
    $slideOver = '.fi-modal-window [id$=".lines"]';

    visitDemo()
        ->click('Edit in a slide-over')
        ->click(cell(1, 0, $slideOver).' .fi-select-input-btn')
        ->typeSlowly(openDropdownSearch(), 'tea', 30)
        ->assertScript(searchResultsFor('tea'))
        ->keys(openDropdownSearch(), 'Escape')
        ->assertVisible('.fi-modal-window')
        ->click(cell(1, 0, $slideOver).' .fi-select-input-btn')
        ->typeSlowly(openDropdownSearch(), 'tea', 30)
        ->assertScript(searchResultsFor('tea'))
        ->keys(openDropdownSearch(), ['ArrowDown', 'Enter'])
        ->assertSeeIn(cell(1, 0, $slideOver).' .fi-select-input-value-label', 'Tea')
        ->keys(cell(1, 1, $slideOver).' input', 'Escape')
        ->wait(0.5)
        ->assertVisible('.fi-modal-window')
        ->assertPresent(cell(2, 0, $slideOver))
        ->assertNoJavaScriptErrors();
});

it('adds rows with the add action and accepts rows pushed by the server', function (): void {
    visitDemo()
        ->click('Add adjustment')
        ->assertPresent(cell(2, 1, ADJUSTMENTS))
        ->click('Add sample lines')
        ->assertSee('Crispy Chocolate 100 g')
        ->assertPresent(cell(4, 0));
});

it('reorders rows by dragging the handle', function (): void {
    $page = visitDemo();

    $page->script(dragRow(cell(0, 0, ADJUSTMENTS).' .fi-data-grid-move-handle', cell(1, 0, ADJUSTMENTS)));

    $page->assertSeeIn(cell(0, 1, ADJUSTMENTS), 'Late delivery')
        ->waitForEvent('networkidle')
        ->click('Save')
        ->assertPresent('[data-saved-state]')
        ->assertScript(savedState('adjustments').'[0].reason', 'Late delivery');
});

it('edits a date, a radio and a field without an inline editor', function (): void {
    visitDemo()
        ->click(cell(1, 4, ADJUSTMENTS))
        ->type(cell(1, 4, ADJUSTMENTS).' input', '2026-11-15')
        ->keys(cell(1, 4, ADJUSTMENTS).' input', 'Enter')
        ->click(cell(0, 2, ADJUSTMENTS).' .fi-select-input-btn')
        ->click('.fi-dropdown-panel:not([style*="display: none"]) [role="option"]:nth-child(2)')
        ->click(cell(1, 5, ADJUSTMENTS))
        ->assertVisible('.fi-modal-window input')
        ->assertSee('Color')
        ->type('.fi-modal-window input', '#123456')
        ->click('.fi-modal-window .fi-modal-heading')
        ->press('Submit')
        ->assertScript(noModalOpen())
        ->click('Save')
        ->assertPresent('[data-saved-state]')
        ->assertScript(savedState('adjustments').'[1].date', '2026-11-15')
        ->assertScript(savedState('adjustments').'[0].kind', 'debit')
        ->assertScript(savedState('adjustments').'[1].color', '#123456');
});

it('keeps frozen columns in place while the rest scrolls sideways', function (): void {
    visitDemo()
        ->assertSeeIn(LINES.' .slick-pane-left .slick-header', 'Product')
        ->assertDontSeeIn(LINES.' .slick-pane-left .slick-header', 'Quantity')
        ->assertSeeIn(LINES.' .slick-pane-right .slick-header', 'Quantity');
});

it('shows the grid in Spanish', function (): void {
    visitDemo('?locale=es')
        ->assertSee('Producto')
        ->assertSee('Seleccione una opción')
        ->assertNoJavaScriptErrors();
});

it('marks editable cells apart from read-only cells', function (): void {
    visitDemo()
        ->assertAttributeContains(cell(0, 1), 'class', 'fi-data-grid-cell-editable')
        ->assertAttributeContains(cell(0, 0), 'class', 'fi-data-grid-cell-editable')
        ->assertAttributeDoesntContain(cell(0, 6), 'class', 'fi-data-grid-cell-editable')
        ->assertAttributeContains(cell(0, 4), 'class', 'fi-data-grid-cell-editable')
        ->assertAttributeDoesntContain(cell(2, 5), 'class', 'fi-data-grid-cell-editable');
});

it('starts editing a text cell by typing on it', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->keys(cell(0, 1).' input', 'Escape')
        ->keys(LINES.' .slick-cell.active', ['9', 'Enter'])
        ->click('Save')
        ->assertPresent('[data-saved-state]')
        ->assertScript(savedState().'[0].quantity', 9);
});

it('sizes the actions column to the inline row actions', function (): void {
    $fits = visitDemo()->script(<<<'JS'
        () => [...document.querySelectorAll('.fi-data-grid-inline-actions')].every((actions) => {
            const cell = actions.closest('.fi-data-grid-actions-cell').getBoundingClientRect()
            const box = actions.getBoundingClientRect()

            return cell.width > 56 && box.left >= cell.left && box.right <= cell.right
        })
        JS);

    expect($fits)->toBeTrue();
});

it('fits a narrow container with the flexible column below the old 240px floor', function (): void {
    $measure = visitDemo()->script(<<<'JS'
        () => {
            const viewport = document.querySelector('[id="form.adjustments"] .slick-viewport-right')

            return {
                overflow: viewport.scrollWidth - viewport.clientWidth,
                flexible: Math.round(document.querySelectorAll('[id="form.adjustments"] .slick-header-column')[1].getBoundingClientRect().width),
            }
        }
        JS);

    expect($measure['overflow'])->toBeLessThanOrEqual(0)
        ->and($measure['flexible'])->toBeGreaterThanOrEqual(160)->toBeLessThan(240);
});

it('opens a grouped row action menu above the rows below it', function (): void {
    $page = visitDemo();

    $page->script(sprintf('document.querySelector(%s).scrollIntoView({ block: "center" })', json_encode(ADJUSTMENTS)));

    $page->click(ADJUSTMENTS.' .slick-row >> nth=0 >> .fi-data-grid-row-actions .fi-dropdown-trigger button')
        ->assertScript(sprintf('[...document.querySelectorAll(%s)].some((panel) => panel.style.display !== "none")', json_encode(ADJUSTMENTS.' .fi-data-grid-row-actions .fi-dropdown-panel')))
        ->assertScript(openDropdownLift(ADJUSTMENTS.' .fi-data-grid-row-actions'), '30/30')
        ->assertScript(<<<'JS'
            () => {
                const item = [...document.querySelectorAll('.fi-dropdown-panel .fi-dropdown-list-item')].find((element) => element.offsetParent !== null)
                const box = item.getBoundingClientRect()

                return item.contains(document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2))
            }
            JS, true)
        ->click('.fi-dropdown-list-item:has-text("Flag for review"):visible')
        ->assertSee('Adjustment flagged for review')
        ->assertNoJavaScriptErrors();
});
