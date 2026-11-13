<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const ORDERS = '[id="form.job_ids"]';

function pickOrderJobs(string $query = ''): AwaitableWebpage
{
    return visit('/demo/order-jobs-demo'.$query)->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);
}

function orderCheckbox(int $order, string $grid = ORDERS): string
{
    return sprintf('%s .fi-data-grid-selection-checkbox[data-selection="parent"][data-key="%d"]', $grid, $order);
}

function jobCheckboxOf(int $job, string $grid = ORDERS): string
{
    return sprintf('%s .fi-data-grid-child-selection-cell .fi-data-grid-selection-checkbox[data-key="%d"]', $grid, $job);
}

function orderRow(int $order): string
{
    return sprintf('%s .slick-row.fi-data-grid-parent-row:has([data-selection="parent"][data-key="%d"])', ORDERS, $order);
}

function orderCount(int $order): string
{
    return sprintf('document.querySelector(%s)?.textContent', json_encode(orderRow($order).' .fi-data-grid-selection-count'));
}

function orderToggle(string $number): string
{
    return sprintf('%s .slick-row.fi-data-grid-parent-row:has-text("%s") .fi-data-grid-row-toggle', ORDERS, $number);
}

function orderLines(): string
{
    return sprintf(<<<'JS'
        [...document.querySelectorAll('%s .grid-canvas .slick-row')]
            .sort((a, b) => parseFloat(a.style.top) - parseFloat(b.style.top))
            .flatMap((row) => row.classList.contains('fi-data-grid-parent-row')
                ? [row.querySelector('.fi-data-grid-entry .fi-in-text-item').textContent.trim()]
                : [...row.querySelectorAll('.fi-data-grid-child-row:not(.fi-data-grid-child-header)')].map((child) => `- ${child.querySelector('.fi-data-grid-child-cell:not(.fi-data-grid-child-selection-cell) .fi-in-text-item').textContent.trim()}`))
        JS, ORDERS);
}

it('puts the caret first, then the checkbox with its count, then the order columns, and the job checkboxes inside', function (): void {
    $page = pickOrderJobs();

    $order = $page->script(sprintf(<<<'JS'
        (() => {
            const row = document.querySelector(%s)
            const left = (selector) => row.querySelector(selector).getBoundingClientRect().left
            return [left('.fi-data-grid-row-toggle') < left('.fi-data-grid-selection-checkbox'), left('.fi-data-grid-selection-checkbox') < left('.fi-data-grid-entry')]
        })()
        JS, json_encode(orderRow(1))));

    expect($order)->toBe([true, true]);

    $page->assertScript(orderCount(1), '0/2')
        ->assertScript(orderCount(2), '0/2')
        ->assertMissing(orderCheckbox(6))
        ->click(orderToggle('ORD-002'))
        ->assertPresent(jobCheckboxOf(3))
        ->assertMissing(jobCheckboxOf(4))
        ->assertPresent(jobCheckboxOf(5))
        ->assertSee('Waiting for a worker')
        ->assertNoJavaScriptErrors();
});

it('selects a closed order\'s selectable jobs and saves job keys even where order and job keys overlap', function (): void {
    pickOrderJobs()
        ->click(orderCheckbox(1))
        ->assertSee('2 selected')
        ->assertScript(orderCount(1), '2/2')
        ->assertMissing(jobCheckboxOf(1))
        ->click(orderCheckbox(2))
        ->assertSee('4 selected')
        ->assertScript(orderCount(2), '2/2')
        ->assertMissing(orderRow(1).'.fi-data-grid-row-selected')
        ->click('Save')
        ->assertScript(savedState('job_ids'), ['1', '2', '3', '5'])
        ->assertNoJavaScriptErrors();
});

it('selecting job 1 leaves order 1 partly selected', function (): void {
    pickOrderJobs()
        ->click(orderToggle('ORD-001'))
        ->click(jobCheckboxOf(1))
        ->assertSee('1 selected')
        ->assertScript(orderCount(1), '1/2')
        ->assertScript(sprintf('(() => { const box = document.querySelector(%s); return [box.checked, box.indeterminate] })()', json_encode(orderCheckbox(1))), [false, true])
        ->assertMissing(orderRow(1).'.fi-data-grid-row-selected');
});

it('keeps each order\'s count right after selecting every matching job and deselecting all', function (): void {
    $page = pickOrderJobs('?perPage=2')
        ->click(ORDERS.' .fi-data-grid-select-all')
        ->assertSee('4 selected')
        ->click(ORDERS.' .fi-data-grid-select-all-matching')
        ->assertSee('9 selected')
        ->click(ORDERS.' .fi-pagination-items [rel="next"]')
        ->assertScript(orderCount(3), '1/1')
        ->assertScript(orderCount(4), '1/1')
        ->click(ORDERS.' .fi-data-grid-deselect-all')
        ->assertSee('0 selected');

    $page->assertScript(orderCount(3), '0/1')
        ->assertNoJavaScriptErrors();
});

it('searches and filters the jobs inside each order and keeps open orders open', function (): void {
    $page = pickOrderJobs()
        ->click(orderToggle('ORD-001'))
        ->type(ORDERS.' input[type="search"]', 'Garland')
        ->assertScript(orderLines(), ['ORD-001', '- Garland', 'ORD-003', 'ORD-005'])
        ->assertScript(orderCount(1), '0/1')
        ->clear(ORDERS.' input[type="search"]')
        ->type(ORDERS.' input[type="search"]', 'ORD-002')
        ->click(orderToggle('ORD-002'))
        ->assertScript(orderLines(), ['ORD-002', '- Tree stand', '- Light string', '- Fraser Fir'])
        ->clear(ORDERS.' input[type="search"]')
        ->click(ORDERS.' .fi-data-grid-filters-trigger')
        ->click(ORDERS.' .fi-data-grid-filter-select .fi-select-input-btn')
        ->type(ORDERS.' .fi-data-grid-filter-select .fi-select-input-search-ctn input', 'Wre')
        ->assertCount(ORDERS.' .fi-data-grid-filter-select [role="option"]:visible', 1)
        ->click(ORDERS.' .fi-data-grid-filter-select [role="option"]:has-text("Wreath")')
        ->click(ORDERS.' .fi-ta-filters-heading')
        ->click(ORDERS.' .fi-data-grid-filters-apply')
        ->assertScript(orderLines(), ['ORD-001', '- Wreath', 'ORD-004', 'ORD-006'])
        ->click(ORDERS.' .fi-data-grid-select-all')
        ->assertSee('2 selected');

    $page->click('Save')
        ->assertScript(savedState('job_ids'), ['2', '7'])
        ->assertNoJavaScriptErrors();
});

it('opens an order from its focused caret with the keyboard', function (): void {
    pickOrderJobs()
        ->keys(orderToggle('ORD-002'), 'Enter')
        ->assertPresent(jobCheckboxOf(3));
});

it('works inside a slide-over action', function (): void {
    $slideOver = '.fi-modal-window [id$=".job_ids"]';

    pickOrderJobs()
        ->click('Add jobs')
        ->assertVisible(orderCheckbox(5, $slideOver))
        ->click(orderCheckbox(5, $slideOver))
        ->assertSeeIn($slideOver, '3 records selected')
        ->click('.fi-modal-window .fi-modal-footer button[type="submit"]')
        ->assertScript(savedState('added'), ['9', '10', '11'])
        ->assertNoJavaScriptErrors();
});

it('lines up the header, order and job checkboxes in one column and keeps the order checkbox whole in a slide-over', function (string $where): void {
    $page = pickOrderJobs();
    $grid = ORDERS;

    if ($where === 'slide-over') {
        $page->click('Add jobs')->assertVisible('.fi-modal-window .slick-row[data-row="0"]');
        $grid = '.fi-modal-window [id$=".job_ids"]';
    }

    $page->click($grid.' .slick-row[data-row="1"] .fi-data-grid-row-toggle')->assertScript(GRID_SETTLED);

    $boxes = $page->script(sprintf(<<<'JS'
        (() => {
            const grid = document.querySelector(%s)
            const box = (selector) => { const rect = grid.querySelector(selector).getBoundingClientRect(); return [Math.round(rect.left), Math.round(rect.width)] }
            return [box('.fi-data-grid-select-all'), box('[data-selection="parent"]'), box('.fi-data-grid-child-selection-cell input')]
        })()
        JS, json_encode($grid)));

    expect(array_unique(array_column($boxes, 0)))->toHaveCount(1)
        ->and(array_column($boxes, 1))->toBe([16, 16, 16]);
})->with(['page', 'slide-over']);

it('clears a multi-select filter on reset', function (): void {
    pickOrderJobs()
        ->click(ORDERS.' .fi-data-grid-filters-trigger')
        ->click(ORDERS.' .fi-data-grid-filter-select .fi-select-input-btn')
        ->click(ORDERS.' .fi-data-grid-filter-select [role="option"]:has-text("Wreath")')
        ->click(ORDERS.' .fi-ta-filters-heading')
        ->click(ORDERS.' .fi-data-grid-filters-apply')
        ->assertScript(orderLines(), ['ORD-001', 'ORD-004', 'ORD-006'])
        ->click(ORDERS.' .fi-data-grid-filters-trigger')
        ->click(ORDERS.' .fi-data-grid-filters-reset')
        ->assertScript(orderLines(), ['ORD-001', 'ORD-002', 'ORD-003', 'ORD-004', 'ORD-005', 'ORD-006'])
        ->click(ORDERS.' .fi-data-grid-filters-trigger')
        ->assertDontSeeIn(ORDERS.' .fi-data-grid-filter-select .fi-select-input-btn', 'Wreath');
});
