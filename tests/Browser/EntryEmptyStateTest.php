<?php

declare(strict_types=1);

const PLANNED = '[id="content.planned_deliveries"]';

it('shows the empty state under the heading, without column headers, when there are no rows', function (): void {
    $page = visitRoute()
        ->assertSeeIn(PLANNED.' .fi-ta-header', 'Planned deliveries')
        ->assertSeeIn(PLANNED.' .fi-ta-empty-state', 'No deliveries yet')
        ->assertSeeIn(PLANNED.' .fi-ta-empty-state', 'Deliveries show up here once they are planned.')
        ->assertSeeIn(PLANNED.' .fi-ta-empty-state', 'Plan a delivery')
        ->assertMissing(PLANNED.' .slick-header-left')
        ->assertMissing(PLANNED.' .fi-data-grid-empty')
        ->assertMissing(PLANNED.' .fi-pagination');

    $page->assertNoJavaScriptErrors();
});

it('runs an empty state action and shows the rows it adds with their full height', function (): void {
    $page = visitRoute()
        ->click(PLANNED.' .fi-ta-empty-state button')
        ->wait(1.5)
        ->assertSeeIn(PLANNED.' .slick-row', 'DLV-2026-0000001')
        ->assertMissing(PLANNED.' .fi-ta-empty-state');

    $sizes = fn (): array => $page->script(sprintf(<<<'JS'
        () => {
            const grid = document.querySelector(%s)

            return {
                rows: [...grid.querySelectorAll('.grid-canvas .slick-row')].map((row) => row.offsetHeight),
                columns: [...grid.querySelectorAll('.slick-header-left .slick-header-column')].map((column) => column.offsetWidth),
            }
        }
        JS, json_encode(PLANNED)));

    $shown = $sizes();
    $page->script("() => document.fonts.dispatchEvent(new Event('loadingdone'))");

    expect($shown)->toBe($sizes())
        ->and($shown['rows'])->toHaveCount(1);
    $page->assertNoJavaScriptErrors();
});

it('keeps saying no results when a search matches nothing in the browser and on the server', function (): void {
    $server = '[id="content.server_deliveries"]';

    visitRoute('?locale=es')
        ->type(ROUTE.' .fi-ta-search-field input', 'zzz')
        ->type($server.' .fi-ta-search-field input', 'zzz')
        ->wait(1.5)
        ->assertSeeIn(ROUTE.' .fi-data-grid-empty', 'Sin resultados.')
        ->assertSeeIn($server.' .fi-data-grid-empty', 'Sin resultados.')
        ->assertMissing(ROUTE.' .fi-ta-empty-state')
        ->assertMissing($server.' .fi-ta-empty-state')
        ->assertVisible($server.' .slick-header-left')
        ->assertNoJavaScriptErrors();
});
