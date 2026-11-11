<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const DISPATCH = '[id="form.dispatch"]';

const HEADER_ALIGNED = <<<'JS'
    (() => {
        const grid = document.querySelector('[id="form.dispatch"]')
        const header = grid.querySelector('.slick-pane-right .slick-header-column .slick-column-name')
        const text = grid.querySelector('.slick-pane-right .slick-cell.l1 .fi-in-text-item')

        return Math.round(header.getBoundingClientRect().left) === Math.round(text.getBoundingClientRect().left)
    })()
    JS;

const COLUMN_FIT = <<<'JS'
    (() => {
        const grid = document.querySelector('[id="form.dispatch"] .fi-data-grid-canvas')
        const parts = '.fi-data-grid-cell-text, .fi-select-input-value-label, .fi-select-input-placeholder, .fi-ta-text, .slick-column-name'

        return JSON.stringify({
            clipped: [...grid.querySelectorAll(`.slick-cell :is(${parts}), .slick-header-column`)].filter((element) => element.scrollWidth > Math.ceil(element.clientWidth)).map((element) => element.textContent.trim()),
            tall: [...grid.querySelectorAll('.slick-row')].some((row) => Math.round(row.getBoundingClientRect().height) > 57),
            overflows: [...grid.querySelectorAll('.slick-viewport')].some((viewport) => viewport.scrollWidth > viewport.clientWidth),
        })
    })()
    JS;

function visitWidths(int $width, string $query = ''): AwaitableWebpage
{
    return visit('/demo/content-widths-demo'.$query)->resize($width, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);
}

function columnWidth(int $column): string
{
    return sprintf('Math.round(document.querySelectorAll(%s)[%d].getBoundingClientRect().width)', json_encode(DISPATCH.' .slick-header-column'), $column);
}

it('fits editable grid columns to their content and shares the spare room', function (): void {
    $page = visitWidths(1920)
        ->assertScript(COLUMN_FIT, json_encode(['clipped' => [], 'tall' => false, 'overflows' => false]));

    expect($page->script(columnWidth(5)))->toBeGreaterThan($page->script(columnWidth(1)))
        ->and($page->script(columnWidth(6)))->toBeGreaterThanOrEqual(128)
        ->and($page->script(HEADER_ALIGNED))->toBeTrue();
});

it('scrolls a narrow editable grid sideways instead of wrapping, with the frozen column in place', function (): void {
    $page = visitWidths(1024)
        ->assertScript(COLUMN_FIT, json_encode(['clipped' => [], 'tall' => false, 'overflows' => true]));

    expect($page->script(<<<'JS'
        () => {
            const grid = document.querySelector('[id="form.dispatch"]')
            const viewport = grid.querySelector('.slick-viewport-right')
            const order = grid.querySelector('.slick-viewport-left .slick-cell')
            const before = order.getBoundingClientRect().left

            viewport.scrollLeft = viewport.scrollWidth

            return viewport.scrollLeft > 0 && order.getBoundingClientRect().left === before
        }
        JS))->toBeTrue();
});

it('widens a column for a value typed into a cell before the server hears of it', function (): void {
    $page = visitWidths(1920);
    $before = $page->script(columnWidth(3));

    $page->click(cell(1, 3, DISPATCH))
        ->keys(cell(1, 3, DISPATCH).' input', [...str_split('CL-0042-LOTE-ESPECIAL-2026-NORTE'), 'Enter'])
        ->assertScript(columnWidth(3).' > '.$before)
        ->assertScript('JSON.parse('.COLUMN_FIT.').clipped.length', 0);
});

it('widens a select column for a longer picked option', function (): void {
    $page = visitWidths(1920);

    $page->click(cell(1, 4, DISPATCH).' .fi-select-input-btn')
        ->assertVisible('.fi-dropdown-panel:not([style*="display: none"])')
        ->click('.fi-dropdown-panel:not([style*="display: none"]) .fi-select-input-option:last-child')
        ->assertScript(sprintf('document.querySelector(%s)?.textContent.trim()', json_encode(cell(1, 4, DISPATCH).' .fi-select-input-value-label')), 'South > Main loading dock')
        ->assertScript('JSON.parse('.COLUMN_FIT.').clipped.length', 0);
});

it('shares the room again when the grid narrows and widens, without wrapping', function (): void {
    $fits = json_encode(['clipped' => [], 'tall' => false, 'overflows' => false]);

    visitWidths(1920)
        ->assertScript(COLUMN_FIT, $fits)
        ->resize(1024, 900)
        ->assertScript(COLUMN_FIT, json_encode(['clipped' => [], 'tall' => false, 'overflows' => true]))
        ->resize(1920, 900)
        ->assertScript(COLUMN_FIT, $fits);
});

it('lines up the first scrolling header with its cells in a compact grid', function (): void {
    visitWidths(1024, '?compact=1')->assertScript(HEADER_ALIGNED, true);
});
