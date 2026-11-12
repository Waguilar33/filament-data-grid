<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const NESTED_CHILD = '[id="form.components"] .fi-data-grid-child-host';

function nestedTrailingRowsDemo(): AwaitableWebpage
{
    return visit('/demo/nested-trailing-rows-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED)
        ->click('[id="form.components"] .fi-data-grid-row-toggle')
        ->assertVisible(NESTED_CHILD.' .slick-row >> nth=0')
        ->assertScript(GRID_SETTLED);
}

it('fits the child grid row actions inside its viewport', function (): void {
    $gap = nestedTrailingRowsDemo()->script(sprintf(<<<'JS'
        (() => {
            const viewport = document.querySelector('%s .slick-viewport').getBoundingClientRect()
            const actions = document.querySelector('%s .fi-data-grid-actions-cell :is(button, a)').getBoundingClientRect()

            return Math.round(viewport.right - actions.right)
        })()
        JS, NESTED_CHILD, NESTED_CHILD));

    expect($gap)->toBeGreaterThanOrEqual(8);
});

it('adds a new blank child row once the last one is filled', function (): void {
    $page = nestedTrailingRowsDemo()->assertCount(NESTED_CHILD.' .slick-row', 2);

    $page->click(NESTED_CHILD.' .slick-row.fi-data-grid-row-trailing .fi-select-input-btn')
        ->click('.fi-dropdown-panel:not([style*="display: none"]) [role="option"]:has-text("Wreath") >> nth=0')
        ->assertCount(NESTED_CHILD.' .slick-row', 3)
        ->assertCount(NESTED_CHILD.' .slick-row.fi-data-grid-row-trailing', 1)
        ->assertNoJavaScriptErrors();
});
