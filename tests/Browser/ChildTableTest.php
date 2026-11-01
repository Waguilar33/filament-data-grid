<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const SHIFT_GRID = '.fi-data-grid-in-table';

function childTableHost(string $method): string
{
    return sprintf('%s .fi-data-grid-child-table-host[data-row-key="%s"]', SHIFT_GRID, $method);
}

function visitShift(): AwaitableWebpage
{
    $page = visit('/demo/cash-shift-demo')->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; !document.querySelector('.fi-data-grid-in-table .slick-header') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

function openMethod(AwaitableWebpage $page, int $row, string $method): AwaitableWebpage
{
    $page->script(sprintf(<<<'JS'
        async () => {
            document.querySelectorAll('.fi-data-grid-in-table .fi-data-grid-row-toggle')[%1$d].click()
            await new Promise((resolve) => setTimeout(resolve, 100))
            document.querySelector('%2$s').scrollIntoView()

            for (let tries = 0; !document.querySelector('%2$s .fi-ta-table') && tries < 80; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }

            await new Promise((resolve) => setTimeout(resolve, 300))
        }
        JS, $row, childTableHost($method)));

    return $page;
}

function rowTopOf(AwaitableWebpage $page, string $label): int
{
    $top = $page->script(sprintf(<<<'JS'
        () => parseInt([...document.querySelectorAll('.fi-data-grid-in-table .slick-row.fi-data-grid-parent-row')].find((row) => row.innerText.includes(%s))?.style.top ?? '-1')
        JS, json_encode($label)));

    return is_int($top) ? $top : -1;
}

it('opens a full Filament table under a parent row, with columns chosen per parent', function (): void {
    $page = visitShift();

    openMethod($page, 0, 'cash')
        ->assertVisible(childTableHost('cash').' .fi-ta-table')
        ->assertSeeIn(childTableHost('cash'), 'R-1001')
        ->assertDontSeeIn(childTableHost('cash').' thead', 'Details');

    openMethod($page, 1, 'card')
        ->assertSeeIn(childTableHost('card').' thead', 'Details')
        ->assertSeeIn(childTableHost('card'), 'R-1101')
        ->assertNoJavaScriptErrors();
});

it('moves the rows below when the child table grows or shrinks', function (): void {
    $page = openMethod(visitShift(), 0, 'cash');
    $before = rowTopOf($page, 'Card');

    $page->select(childTableHost('cash').' .fi-pagination-records-per-page-select:not(.fi-compact) select', 'all')->wait(2);
    $after = rowTopOf($page, 'Card');

    $page->type(childTableHost('cash').' .fi-ta-search-field input', 'R-1034')->wait(2);
    $searched = rowTopOf($page, 'Card');

    expect($after)->toBeGreaterThan($before + 500)
        ->and($searched)->toBeLessThan($before);

    $page->assertNoJavaScriptErrors();
});

it('keeps each child table and its state when the page re-renders', function (): void {
    $page = openMethod(visitShift(), 0, 'cash');

    $page->type(childTableHost('cash').' .fi-ta-search-field input', 'R-100')->wait(2);

    $page->script(<<<'JS'
        async () => {
            await Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).$refresh()
            await new Promise((resolve) => setTimeout(resolve, 500))
        }
        JS);

    $page->assertVisible(childTableHost('cash').' .fi-ta-table')
        ->assertValue(childTableHost('cash').' .fi-ta-search-field input', 'R-100')
        ->assertSeeIn(childTableHost('cash'), 'Showing 1 to 9 of 9 results')
        ->assertCount(SHIFT_GRID.' .fi-data-grid-child-table-host', 4)
        ->assertAttribute(SHIFT_GRID.' .fi-data-grid-parent-row-expanded .fi-data-grid-row-toggle', 'aria-expanded', 'true')
        ->assertNoJavaScriptErrors();
});

it('selects child records and runs a bulk action through its modal', function (): void {
    openMethod(visitShift(), 0, 'cash')
        ->click(childTableHost('cash').' thead input[type="checkbox"]')
        ->wait(1)
        ->assertSeeIn(childTableHost('cash'), '10 records selected')
        ->click(childTableHost('cash').' button:has-text("Reconcile")')
        ->wait(1)
        ->assertVisible('.fi-modal-window:has-text("Reconcile")')
        ->click('.fi-modal-window button:has-text("Confirm")')
        ->wait(1.5)
        ->assertSee('Reconciled 10 movements')
        ->assertNoJavaScriptErrors();
});

it('drops the child tables of parents that Filament filters out, and mounts them again when they return', function (): void {
    $page = openMethod(visitShift(), 0, 'cash');

    $page->type('.fi-ta-header-toolbar .fi-ta-search-field input >> nth=0', 'card')->wait(2);

    $page->assertCount(SHIFT_GRID.' .fi-data-grid-child-table-host', 1)
        ->assertMissing(childTableHost('cash'))
        ->assertNotPresent(SHIFT_GRID.' .fi-data-grid-parent-row-expanded')
        ->assertScript("document.querySelector('".childTableHost('card')."').style.display", 'none');

    $page->clear('.fi-ta-header-toolbar .fi-ta-search-field input >> nth=0')->wait(2);

    openMethod($page, 0, 'cash')
        ->assertSeeIn(childTableHost('cash'), 'R-1001')
        ->assertNoJavaScriptErrors();
});

it('opens child tables in a standalone entry too', function (): void {
    $page = visitShift();

    $page->script(<<<'JS'
        async () => {
            document.querySelectorAll('.fi-data-grid-entry-grid:not(.fi-data-grid-in-table) .fi-data-grid-row-toggle')[3].click()

            for (let tries = 0; !document.querySelector('.fi-data-grid-entry-grid:not(.fi-data-grid-in-table) [data-row-key="voucher"] .fi-ta-table') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    $page->assertSeeIn('.fi-data-grid-entry-grid:not(.fi-data-grid-in-table) [data-row-key="voucher"]', 'V-017')
        ->assertNoJavaScriptErrors();
});

it('never scrolls the grid inside itself when a focused element overflows it', function (): void {
    $page = openMethod(visitShift(), 0, 'cash');

    $scrolled = $page->script(<<<'JS'
        () => [...document.querySelectorAll('.fi-data-grid-entry-content, .fi-data-grid-viewport, .fi-data-grid-child-host')].map((container) => {
            const filler = document.createElement('div')
            filler.style.cssText = 'position: absolute; top: 100%; height: 200px'
            filler.tabIndex = -1
            container.append(filler)
            filler.focus()
            const scrollTop = container.scrollTop
            filler.remove()

            return scrollTop
        })
        JS);

    expect($scrolled)->not->toBeEmpty()->each->toBe(0);
});
