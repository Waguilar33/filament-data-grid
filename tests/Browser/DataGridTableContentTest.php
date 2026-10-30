<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const TABLE_GRID = '.fi-data-grid-in-table';

function tableRow(int $row): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"]', TABLE_GRID, $row * 44);
}

function visitTable(): AwaitableWebpage
{
    $page = visit('/demo/delivery-table-demo')->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; !document.querySelector('.fi-data-grid-in-table .slick-header') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

it('opens rows inside a Filament table and keeps them open while Filament searches', function (): void {
    visitTable()
        ->click(tableRow(0).' .fi-data-grid-row-toggle')
        ->assertSeeIn(TABLE_GRID.' .fi-data-grid-child-header', 'Product')
        ->type('.fi-ta-search-field input', '0000001')
        ->wait(1.5)
        ->assertSeeIn(tableRow(0), 'DLV-2026-0000001')
        ->assertAttribute(tableRow(0).' .fi-data-grid-row-toggle', 'aria-expanded', 'true')
        ->assertSeeIn(TABLE_GRID.' .fi-data-grid-child-header', 'Product')
        ->assertNoJavaScriptErrors();
});

it('uses Filament filters in a slide-over and Filament pagination', function (): void {
    visitTable()
        ->assertSee('Showing 1 to 10 of 240 results')
        ->click('.fi-ta-header-toolbar button[title="Filter"]')
        ->wait(1)
        ->assertVisible('.fi-modal-slide-over .fi-modal-window')
        ->click('.fi-modal-slide-over .fi-select-input-btn')
        ->wait(0.5)
        ->click('.fi-modal-slide-over .fi-dropdown-panel li:has-text("Ana Torres")')
        ->click('Apply filters')
        ->wait(1.5)
        ->assertSee('Active filters')
        ->assertSee('Showing 1 to 10 of 21 results')
        ->assertSeeIn(tableRow(0), 'Ana Torres')
        ->click('.fi-pagination-items [rel="next"]')
        ->wait(1.5)
        ->assertSee('Showing 11 to 20 of 21 results')
        ->assertSeeIn(tableRow(0), 'Ana Torres')
        ->assertNoJavaScriptErrors();
});

it('runs the table record actions and opens the record from its row', function (): void {
    visitTable()
        ->click(tableRow(0).' button:has-text("Reschedule")')
        ->wait(1)
        ->assertSee('New date')
        ->click('.fi-modal-slide-over.fi-modal-open .fi-modal-close-btn, .fi-modal-slide-over:has-text("New date") .fi-modal-close-btn')
        ->wait(0.5)
        ->click(tableRow(1).' button:has-text("Deliver")')
        ->wait(1)
        ->click('.fi-modal-window button:has-text("Confirm")')
        ->wait(1)
        ->assertSee('Delivery DLV-2026-0000002 marked as delivered')
        ->click(tableRow(2).' .fi-data-grid-record-link')
        ->wait(1.5)
        ->assertUrlIs('*delivery-route-demo*')
        ->assertNoJavaScriptErrors();
});

it('shows a tooltip on an icon-only cell', function (): void {
    visitTable()
        ->hover(tableRow(0).' .slick-cell.l4 svg')
        ->wait(0.5)
        ->assertSee('Shipment');
});
