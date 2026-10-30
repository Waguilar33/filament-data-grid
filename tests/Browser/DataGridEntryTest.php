<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const ROUTE = '[id="content.deliveries"]';

function routeRow(int $row): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"]', ROUTE, $row * 44);
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
        ->assertSeeIn(routeRow(1), 'Product')
        ->assertSeeIn(routeRow(2), 'Light Coffee 100 g')
        ->assertSeeIn(routeRow(4), 'DLV-2026-0000002')
        ->click(routeRow(4).' .slick-cell.l2')
        ->assertSeeIn(routeRow(5), 'Product')
        ->assertAttribute(routeRow(4).' .fi-data-grid-row-toggle', 'aria-expanded', 'true')
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
        ->click(routeRow(9).' .fi-data-grid-row-toggle')
        ->type(ROUTE.' .fi-data-grid-search input', 'karen')
        ->wait(0.5)
        ->assertSee('Showing 1 to 2 of 2 results')
        ->assertSeeIn(routeRow(0), 'DLV-2026-0000010')
        ->assertSeeIn(routeRow(1), 'Product')
        ->assertScript(visibleRowCount('.fi-data-grid-parent-row'), 2)
        ->type(ROUTE.' .fi-data-grid-search input', 'nobody')
        ->wait(0.5)
        ->assertSee('No results.')
        ->assertNoJavaScriptErrors();
});

it('shows badges and numbers from the entries', function (): void {
    visitRoute()
        ->assertSeeIn(routeRow(3).' .fi-badge', 'Partial')
        ->click(routeRow(3).' .fi-data-grid-row-toggle')
        ->assertSeeIn(routeRow(4), 'Product')
        ->assertPresent(routeRow(7).' .fi-badge')
        ->assertSeeIn(routeRow(5), '5');
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
        ->type($server.' .fi-data-grid-search input', 'karen')
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
