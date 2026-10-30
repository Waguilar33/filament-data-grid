<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const ROUTE = '[id="content.deliveries"]';

function routeRow(int $row): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"]', ROUTE, $row * 44);
}

function routeRowAt(int $top): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"]', ROUTE, $top);
}

function parentRow(string $deliveryNumber): string
{
    return sprintf('%s .fi-data-grid-parent-row:has-text("%s")', ROUTE, $deliveryNumber);
}

function childRow(string $product): string
{
    return sprintf('%s .fi-data-grid-child-row-wrapper:has-text("%s")', ROUTE, $product);
}

function rowHeight(string $text): string
{
    return sprintf(
        '[...document.querySelectorAll(%s)].find((row) => row.textContent.includes(%s)).getBoundingClientRect().height',
        json_encode(ROUTE.' .slick-row'),
        json_encode($text),
    );
}

function badgesFitTheirCells(string $text): string
{
    return sprintf(<<<'JS'
        (() => {
            const row = [...document.querySelectorAll(%s)].find((row) => row.textContent.includes(%s))
            const badges = [...row.querySelectorAll('.fi-badge')]

            return badges.length > 0 && badges.every((badge) => {
                const inner = badge.getBoundingClientRect()
                const outer = badge.closest('.fi-data-grid-child-cell, .slick-cell').getBoundingClientRect()

                return inner.top >= outer.top && inner.bottom <= outer.bottom && inner.left >= outer.left && inner.right <= outer.right
            })
        })()
        JS, json_encode(ROUTE.' .slick-row'), json_encode($text));
}

function rowsAreStacked(): string
{
    return sprintf(<<<'JS'
        (() => {
            const rows = [...document.querySelectorAll(%s)].sort((a, b) => a.offsetTop - b.offsetTop)

            return rows.every((row, index) => index === 0 || row.offsetTop === rows[index - 1].offsetTop + rows[index - 1].offsetHeight)
        })()
        JS, json_encode(ROUTE.' .slick-row'));
}

function visitRoute(string $query = ''): AwaitableWebpage
{
    $page = visit('/demo/delivery-route-demo'.$query)->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; !document.querySelector('.fi-data-grid-canvas .slick-header') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

function visibleRowCount(string $selector): string
{
    return sprintf('document.querySelectorAll(%s).length', json_encode(ROUTE.' '.$selector));
}

it('shows parents collapsed and opens several of them', function (): void {
    visitRoute()
        ->assertSeeIn(routeRow(0), 'DLV-2026-0000001')
        ->assertScript(visibleRowCount('.fi-data-grid-child-row-wrapper'), 0)
        ->assertAttribute(routeRow(0).' .fi-data-grid-row-toggle', 'aria-expanded', 'false')
        ->click(routeRow(0).' .fi-data-grid-row-toggle')
        ->assertAttribute(routeRow(0).' .fi-data-grid-row-toggle', 'aria-expanded', 'true')
        ->assertSeeIn(ROUTE.' .fi-data-grid-child-header', 'Product')
        ->assertSeeIn(ROUTE.' .fi-data-grid-child-row-wrapper:has-text("Light Coffee 100 g")', 'Light Coffee 100 g')
        ->click(parentRow('DLV-2026-0000002').' .slick-cell.l2')
        ->assertScript(visibleRowCount('.fi-data-grid-child-header'), 2)
        ->assertAttribute(parentRow('DLV-2026-0000002').' .fi-data-grid-row-toggle', 'aria-expanded', 'true')
        ->assertAttribute(routeRow(0).' .fi-data-grid-row-toggle', 'aria-expanded', 'true')
        ->click(routeRow(0).' .fi-data-grid-row-toggle')
        ->assertSeeIn(routeRow(1), 'DLV-2026-0000002')
        ->assertNoJavaScriptErrors();
});

it('pages by parents, so open children never push a parent to the next page', function (): void {
    visitRoute()
        ->assertSee('Showing 1 to 10 of 23 results')
        ->assertScript(visibleRowCount('.fi-data-grid-parent-row'), 10)
        ->click(routeRow(0).' .fi-data-grid-row-toggle')
        ->assertScript(visibleRowCount('.fi-data-grid-parent-row'), 10)
        ->click(ROUTE.' .fi-pagination-items [rel="next"]')
        ->assertSee('Showing 11 to 20 of 23 results')
        ->assertSeeIn(routeRow(0), 'DLV-2026-0000011')
        ->click(ROUTE.' .fi-pagination-items [rel="next"]')
        ->assertSee('Showing 21 to 23 of 23 results')
        ->assertScript(visibleRowCount('.fi-data-grid-parent-row'), 3)
        ->assertNoJavaScriptErrors();
});

it('searches parents and keeps their open children with them', function (): void {
    visitRoute()
        ->click(parentRow('DLV-2026-0000010').' .fi-data-grid-row-toggle')
        ->type(ROUTE.' .fi-ta-search-field input', 'karen')
        ->wait(0.5)
        ->assertSee('Showing 1 to 2 of 2 results')
        ->assertSeeIn(routeRow(0), 'DLV-2026-0000010')
        ->assertSeeIn(ROUTE.' .fi-data-grid-child-header', 'Product')
        ->assertScript(visibleRowCount('.fi-data-grid-parent-row'), 2)
        ->type(ROUTE.' .fi-ta-search-field input', 'nobody')
        ->wait(0.5)
        ->assertSee('No results.')
        ->assertNoJavaScriptErrors();
});

it('shows badges and numbers from the entries', function (): void {
    visitRoute()
        ->assertSeeIn(parentRow('DLV-2026-0000004').' .fi-badge', 'Partial')
        ->click(parentRow('DLV-2026-0000004').' .fi-data-grid-row-toggle')
        ->assertSeeIn(childRow('Rustic Tea 100 g'), '5')
        ->assertSeeIn(childRow('Wild Bread 100 g').' .fi-badge', 'U-0042');
});

it('grows a child row to fit the unit code under its product', function (): void {
    visitRoute()
        ->click(parentRow('DLV-2026-0000002').' .fi-data-grid-row-toggle')
        ->assertSeeIn(childRow('Tropical Tea 100 g').' .fi-badge', 'U-0021')
        ->assertScript(rowHeight('Fresh Tea 100 g'), 44)
        ->assertScript(rowHeight('Tropical Tea 100 g').' > 44', true)
        ->assertScript(badgesFitTheirCells('Tropical Tea 100 g'), true)
        ->assertScript(rowsAreStacked(), true)
        ->assertNoJavaScriptErrors();
});

it('grows a parent row whose badges wrap onto a second line', function (): void {
    visitRoute()
        ->assertScript(rowHeight('DLV-2026-0000001'), 44)
        ->assertScript(rowHeight('DLV-2026-0000004').' > 44', true)
        ->assertScript(badgesFitTheirCells('DLV-2026-0000004'), true)
        ->assertScript(rowsAreStacked(), true)
        ->assertNoJavaScriptErrors();
});

it('keeps rows stacked and badges inside their cells after the window is resized', function (): void {
    visitRoute()
        ->click(parentRow('DLV-2026-0000002').' .fi-data-grid-row-toggle')
        ->resize(800, 900)
        ->wait(0.5)
        ->assertScript(rowsAreStacked(), true)
        ->assertScript(badgesFitTheirCells('Tropical Tea 100 g'), true)
        ->assertScript(badgesFitTheirCells('DLV-2026-0000004'), true)
        ->resize(1600, 900)
        ->wait(0.5)
        ->assertScript(rowsAreStacked(), true)
        ->assertScript(badgesFitTheirCells('Tropical Tea 100 g'), true)
        ->assertScript(badgesFitTheirCells('DLV-2026-0000004'), true)
        ->assertNoJavaScriptErrors();
});

it('shows the grid in Spanish', function (): void {
    visitRoute('?locale=es')
        ->assertSee('Se muestran de 1 a 10 de 23 resultados')
        ->assertAttribute(routeRow(0).' .fi-data-grid-row-toggle', 'aria-label', 'Expandir fila');
});

it('loads pages and search results from the server and keeps them across re-renders', function (): void {
    $server = '[id="content.server_deliveries"]';
    $firstRow = $server.' .slick-row[style*="top: 0px"]';

    $page = visitRoute()
        ->assertSeeIn($server.' .fi-pagination-overview', 'Showing 1 to 10 of 240 results')
        ->click($server.' .fi-pagination-items [rel="next"]')
        ->wait(1)
        ->assertSeeIn($server.' .fi-pagination-overview', 'Showing 11 to 20 of 240 results')
        ->assertSeeIn($firstRow, 'DLV-2026-0000011')
        ->type($server.' .fi-ta-search-field input', 'karen')
        ->wait(1.5)
        ->assertSeeIn($server.' .fi-pagination-overview', 'Showing 1 to 10 of 21 results')
        ->click($server.' .fi-pagination-items [rel="next"]')
        ->wait(1)
        ->assertSeeIn($firstRow, 'DLV-2026-0000120');

    $page->script('() => Livewire.all()[0].$wire.$refresh()');

    $page->wait(1.5)
        ->assertSeeIn($server.' .fi-pagination-overview', 'Showing 11 to 20 of 21 results')
        ->assertSeeIn($firstRow, 'DLV-2026-0000120')
        ->assertNoJavaScriptErrors();
});

it('filters rows with Filament filters in the browser and on the server', function (): void {
    $server = '[id="content.server_deliveries"]';

    visitRoute()
        ->assertSeeIn(ROUTE.' .fi-data-grid-filters-trigger', '0')
        ->click(ROUTE.' .fi-data-grid-filters-trigger')
        ->select(ROUTE.' .fi-ta-filters select', 'partial')
        ->click(ROUTE.' .fi-data-grid-filters-apply')
        ->assertSeeIn(ROUTE.' .fi-pagination-overview', 'Showing 1 to 5 of 5 results')
        ->assertSeeIn(ROUTE.' .fi-data-grid-filters-trigger', '1')
        ->click($server.' .fi-data-grid-filters-trigger')
        ->select($server.' .fi-ta-filters select', 'partial')
        ->click($server.' .fi-data-grid-filter-option:has-text("Karen Ortiz") input')
        ->click($server.' .fi-data-grid-filters-apply')
        ->wait(1)
        ->assertSeeIn($server.' .fi-pagination-overview', 'Showing 1 to 5 of 5 results')
        ->click($server.' .fi-data-grid-filters-trigger')
        ->click($server.' .fi-data-grid-filters-reset')
        ->wait(1)
        ->assertSeeIn($server.' .fi-pagination-overview', 'Showing 1 to 10 of 240 results')
        ->assertNoJavaScriptErrors();
});

it('shows the details above and the notes below the child table of an open row', function (): void {
    visitRoute()
        ->click(ROUTE.' .slick-row:has-text("DLV-2026-0000004") .fi-data-grid-row-toggle')
        ->wait(0.5)
        ->assertSeeIn(ROUTE.' .fi-data-grid-child-slot-above', '128 Elm Street, Suite 5')
        ->assertSeeIn(ROUTE.' .fi-data-grid-child-slot-above', 'R-05')
        ->assertSeeIn(ROUTE.' .fi-data-grid-child-slot-below', 'Call before arriving')
        ->assertScript(sprintf('document.querySelector(%s).getBoundingClientRect().height >= document.querySelector(%s).scrollHeight', json_encode(ROUTE.' .fi-data-grid-child-slot-above'), json_encode(ROUTE.' .fi-data-grid-child-slot-above')), true)
        ->click(ROUTE.' .slick-row:has-text("DLV-2026-0000004") .fi-data-grid-row-toggle')
        ->click(ROUTE.' .slick-row:has-text("DLV-2026-0000001") .fi-data-grid-row-toggle')
        ->wait(0.5)
        ->assertPresent(ROUTE.' .fi-data-grid-child-slot-above')
        ->assertMissing(ROUTE.' .fi-data-grid-child-slot-below')
        ->assertNoJavaScriptErrors();
});

it('centres badges vertically in their cells', function (): void {
    $page = visitRoute()
        ->click(ROUTE.' .slick-row:has-text("DLV-2026-0000004") .fi-data-grid-row-toggle')
        ->wait(0.5);

    $gaps = $page->script(sprintf(<<<'JS'
        () => [...document.querySelectorAll(%s)].map((badge) => {
            const cell = badge.closest('.slick-cell, .fi-data-grid-child-cell').getBoundingClientRect()
            const box = badge.getBoundingClientRect()

            return Math.abs((box.top - cell.top) - (cell.bottom - box.bottom))
        })
        JS, json_encode(ROUTE.' .fi-data-grid-parent-row .slick-cell.l3 .fi-badge, '.ROUTE.' .fi-data-grid-child-row:not(.fi-data-grid-child-header) .fi-data-grid-child-cell:nth-child(2) .fi-badge')));

    expect($gaps)->not->toBeEmpty()
        ->each->toBeLessThanOrEqual(1);
});
