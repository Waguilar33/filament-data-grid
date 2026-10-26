<?php

declare(strict_types=1);

const LINES = '[id="form.lines"]';

function cell(int $row, int $column, string $grid = LINES): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"] .slick-cell.l%d', $grid, $row * 44, $column);
}

function savedLines(): string
{
    return 'JSON.parse(document.querySelector("[data-saved-state]").textContent).lines';
}

it('picks a product by server search, recalculates the row and appends a blank row', function (): void {
    $page = visit('/demo/data-grid-demo')
        ->assertSee('Creamy Coffee 100 g')
        ->click(cell(2, 0))
        ->typeSlowly(cell(2, 0).' input', 'honey', 30)
        ->assertVisible('.fi-data-grid-autocomplete')
        ->keys(cell(2, 0).' input', ['ArrowDown', 'ArrowDown', 'Enter'])
        ->wait(1.5);

    $page->assertSeeIn(cell(2, 0), 'Roasted Honey 100 g')
        ->assertScript('document.activeElement.matches(".fi-data-grid-editor-input")')
        ->assertSeeIn(cell(2, 6), 'GTQ')
        ->assertPresent(cell(3, 0))
        ->assertNoJavaScriptErrors();
});

it('saves a value typed into a cell without pressing Enter', function (): void {
    visit('/demo/data-grid-demo')
        ->click(cell(0, 1))
        ->typeSlowly(cell(0, 1).' input', '7', 30)
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedLines().'[0].quantity', 7)
        ->assertScript(savedLines().'[0].note', 'seeded')
        ->assertScript(savedLines().'.length', 2);
});

it('shows a server error on the cell of its row and keeps it after deleting another row', function (): void {
    $page = visit('/demo/data-grid-demo')
        ->click(cell(1, 1))
        ->keys(cell(1, 1).' input', ['Backspace', 'Backspace', 'Backspace', 'Enter'])
        ->wait(1)
        ->click('Save')
        ->wait(1.5);

    $page->assertSee('Row 2 · Quantity: The quantity field is required.')
        ->assertAttributeContains(cell(1, 1), 'class', 'fi-data-grid-cell-invalid')
        ->click(cell(0, 7).' button')
        ->wait(1.5)
        ->assertSee('Row 1 · Quantity: The quantity field is required.')
        ->assertAttributeContains(cell(0, 1), 'class', 'fi-data-grid-cell-invalid');
});

it('searches inside a slide-over without closing it on Escape', function (): void {
    $slideOver = '.fi-modal-window [id$=".lines"]';

    $page = visit('/demo/data-grid-demo')
        ->click('Edit in a slide-over')
        ->wait(1)
        ->click(cell(1, 0, $slideOver))
        ->typeSlowly(cell(1, 0, $slideOver).' input', 'tea', 30)
        ->assertVisible('.fi-data-grid-autocomplete')
        ->wait(1.5);

    $option = $page->script('() => document.querySelector(".fi-data-grid-autocomplete > div:nth-child(2)").textContent.split(" · ")[0]');

    expect($option)->toContain('Tea');

    $page->click('.fi-data-grid-autocomplete > div:nth-child(2)')
        ->wait(1.5)
        ->assertSeeIn(cell(1, 0, $slideOver), (string) $option)
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
        ->assertPresent(cell(1, 0, '[id="form.adjustments"]'))
        ->click('Add sample lines')
        ->wait(1)
        ->assertSee('Crispy Chocolate 100 g')
        ->assertPresent(cell(4, 0));
});

it('shows the grid in Spanish', function (): void {
    visit('/demo/data-grid-demo?locale=es')
        ->assertSee('Producto')
        ->assertSee('Seleccione una opción')
        ->assertNoJavaScriptErrors();
});

it('keeps frozen columns in place while the rest scrolls sideways', function (): void {
    visit('/demo/data-grid-demo')
        ->assertSeeIn(LINES.' .slick-pane-left .slick-header', 'Product')
        ->assertDontSeeIn(LINES.' .slick-pane-left .slick-header', 'Quantity')
        ->assertSeeIn(LINES.' .slick-pane-right .slick-header', 'Quantity');
});
