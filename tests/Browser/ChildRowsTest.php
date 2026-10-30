<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

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
    return sprintf('%s .slick-row[style*="top: %dpx"] .slick-cell.l%d', childGrid($position), $row * 44, $column);
}

function visitDispatch(): AwaitableWebpage
{
    $page = visit('/demo/dispatch-demo')->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; document.querySelectorAll('.fi-data-grid-child-host .slick-header').length < 3 && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

it('updates the parent total live while the next child cell is being edited', function (): void {
    visitDispatch()
        ->assertSeeIn(productRow(44).' .slick-cell.l3', '5')
        ->click(childCell(1, 0, 2))
        ->keys(childCell(1, 0, 2).' input', ['6', 'Enter'])
        ->keys(childCell(1, 1, 2).' input', ['3'])
        ->wait(1.5)
        ->assertSeeIn(productRow(44).' .slick-cell.l3', '11')
        ->assertValue(childCell(1, 1, 2).' input', '3')
        ->keys(childCell(1, 1, 2).' input', ['Enter'])
        ->wait(1.5)
        ->assertSeeIn(productRow(44).' .slick-cell.l3', '9')
        ->assertNoJavaScriptErrors();
});

it('moves to the next unit after a scanned code and Enter', function (): void {
    visitDispatch()
        ->click(childCell(2, 1, 1))
        ->keys(childCell(2, 1, 1).' input', ['E', 'M', '-', '9', 'Enter'])
        ->wait(1.5)
        ->assertSeeIn(childCell(2, 1, 1), 'EM-9')
        ->assertNoJavaScriptErrors();
});

it('opens the product that holds a server error and lists the error under the grid', function (): void {
    visitDispatch()
        ->click(PRODUCTS.' .slick-row:has-text("Espresso machine") .fi-data-grid-row-toggle')
        ->wait(0.5)
        ->click('Save')
        ->wait(2)
        ->assertSee('Units row 2')
        ->assertPresent(childGrid(3).' .fi-data-grid-cell-invalid')
        ->assertNoJavaScriptErrors();
});
