<?php

declare(strict_types=1);

const PRODUCTS = '[id="form.products"]';

function productRow(int $top): string
{
    return sprintf('%s .grid-canvas .slick-row[style*="top: %dpx"]', PRODUCTS, $top);
}

function childGrid(int $position): string
{
    return sprintf('%s .fi-data-grid-child-layer > .fi-data-grid-child-host:nth-of-type(%d)', PRODUCTS, $position);
}

function childCell(int $position, int $row, int $column): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"] .slick-cell.l%d', childGrid($position), $row * TABLE_ROW_HEIGHT, $column);
}

it('updates the parent total live while the next child cell is being edited', function (): void {
    visitDispatch()
        ->assertSeeIn(productRow(TABLE_ROW_HEIGHT).' .slick-cell.l3', '5')
        ->click(childCell(1, 0, 2))
        ->keys(childCell(1, 0, 2).' input', ['6', 'Enter'])
        ->keys(childCell(1, 1, 2).' input', ['3'])
        ->assertSeeIn(productRow(TABLE_ROW_HEIGHT).' .slick-cell.l3', '11')
        ->assertValue(childCell(1, 1, 2).' input', '3')
        ->keys(childCell(1, 1, 2).' input', ['Enter'])
        ->assertSeeIn(productRow(TABLE_ROW_HEIGHT).' .slick-cell.l3', '9')
        ->assertNoJavaScriptErrors();
});

it('moves to the next unit after a scanned code and Enter', function (): void {
    visitDispatch()
        ->click(childCell(2, 1, 1))
        ->keys(childCell(2, 1, 1).' input', ['E', 'M', '-', '9', 'Enter'])
        ->assertSeeIn(childCell(2, 1, 1), 'EM-9')
        ->assertNoJavaScriptErrors();
});

it('opens the product that holds a server error and lists the error under the grid', function (): void {
    visitDispatch()
        ->click(PRODUCTS.' .slick-row:has-text("Espresso machine") .fi-data-grid-row-toggle')
        ->assertCount(PRODUCTS.' .fi-data-grid-child-host:has-text("EM-4471")', 0)
        ->click('Save')
        ->assertSee('Units row 2')
        ->assertPresent(PRODUCTS.' .fi-data-grid-child-host:has-text("EM-4471") .fi-data-grid-cell-invalid')
        ->assertNoJavaScriptErrors();
});
