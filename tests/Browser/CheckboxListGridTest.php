<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const PICK = '[id="form.job_ids"]';

function pickJobs(string $query = ''): AwaitableWebpage
{
    return visit('/demo/jobs-demo'.$query)->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);
}

function jobCheckbox(string $key): string
{
    return sprintf('%s .fi-data-grid-selection-checkbox[data-key="%s"]', PICK, $key);
}

function groupCheckbox(string $group): string
{
    return sprintf('%s .fi-data-grid-selection-checkbox[data-group="%s"]', PICK, $group);
}

function checkedKeys(): string
{
    return sprintf('[...document.querySelectorAll(%s)].filter((box) => box.checked).map((box) => box.dataset.key)', json_encode(PICK.' .fi-data-grid-selection-checkbox[data-key]'));
}

function selectAllState(): string
{
    return sprintf('(() => { const box = document.querySelector(%s); return [box.checked, box.indeterminate] })()', json_encode(PICK.' .fi-data-grid-select-all'));
}

it('selects rows into the field state and saves their keys', function (): void {
    pickJobs()
        ->click(jobCheckbox('job-1'))
        ->assertSee('1 selected')
        ->assertPresent(PICK.' .slick-row.fi-data-grid-row-selected')
        ->click(jobCheckbox('job-5'))
        ->assertSee('2 selected')
        ->click('Save')
        ->assertScript(savedState('job_ids'), ['job-1', 'job-5'])
        ->assertNoJavaScriptErrors();
});

it('shows no checkbox on a row that cannot be selected, and none on a group without selectable rows', function (): void {
    pickJobs()
        ->assertMissing(jobCheckbox('job-4'))
        ->assertPresent(jobCheckbox('job-3'))
        ->assertMissing(groupCheckbox('S-002'))
        ->assertSee('Waiting for its unit');
});

it('selects every selectable row of a group from its header', function (): void {
    pickJobs('?count=12')
        ->click(groupCheckbox('S-001'))
        ->assertScript(checkedKeys(), ['job-1', 'job-2'])
        ->assertScript(sprintf('document.querySelector(%s).checked', json_encode(groupCheckbox('S-001'))), true)
        ->click(groupCheckbox('S-001'))
        ->assertScript(checkedKeys(), []);
});

it('selects every matching row from the header and keeps the selection when the search changes', function (): void {
    $page = pickJobs()
        ->type(PICK.' input[type="search"]', 'Garland')
        ->assertScript(sprintf('document.querySelectorAll(%s).length', json_encode(PICK.' .fi-data-grid-selection-checkbox[data-key]')), 2)
        ->click(PICK.' .fi-data-grid-select-all')
        ->assertSee('2 selected')
        ->assertScript(selectAllState(), [true, false])
        ->clear(PICK.' input[type="search"]')
        ->assertScript(checkedKeys(), ['job-1', 'job-6'])
        ->assertScript(selectAllState(), [false, true]);

    $page->click(PICK.' .fi-data-grid-select-all')
        ->assertSee('5 selected')
        ->assertNoJavaScriptErrors();
});

it('keeps the selection when a filter hides selected rows', function (): void {
    pickJobs()
        ->click(jobCheckbox('job-2'))
        ->assertSee('1 selected')
        ->click(PICK.' .fi-data-grid-filters-trigger')
        ->select(PICK.' .fi-ta-filters select', 'Garland')
        ->click(PICK.' .fi-data-grid-filters-apply')
        ->assertMissing(jobCheckbox('job-2'))
        ->click(PICK.' .fi-data-grid-select-all')
        ->assertSee('3 selected')
        ->click('Save')
        ->assertScript(savedState('job_ids'), ['job-2', 'job-1', 'job-6']);
});

it('keeps the selection across pages and selects only the current page from the header', function (): void {
    pickJobs('?count=12&perPage=4')
        ->click(jobCheckbox('job-1'))
        ->assertSee('1 selected')
        ->click(PICK.' .fi-pagination-items [rel="next"]')
        ->assertMissing(jobCheckbox('job-1'))
        ->click(PICK.' .fi-data-grid-select-all')
        ->assertSee('4 selected')
        ->click(PICK.' .fi-pagination-items [rel="prev"]')
        ->assertScript(checkedKeys(), ['job-1'])
        ->assertNoJavaScriptErrors();
});

it('selects every matching row across pages from the selection bar, then deselects all', function (): void {
    $page = pickJobs('?count=12&perPage=2')
        ->click(PICK.' .fi-data-grid-filters-trigger')
        ->select(PICK.' .fi-ta-filters select', 'Garland')
        ->click(PICK.' .fi-data-grid-filters-apply')
        ->click(PICK.' .fi-data-grid-select-all');

    $page->assertSeeIn(PICK.' .fi-data-grid-selection-indicator', '2 records selected')
        ->assertSeeIn(PICK.' .fi-data-grid-select-all-matching', 'Select all 3')
        ->click(PICK.' .fi-data-grid-select-all-matching')
        ->assertSee('3 selected')
        ->assertMissing(PICK.' .fi-data-grid-select-all-matching')
        ->click(PICK.' .fi-data-grid-deselect-all')
        ->assertSee('0 selected')
        ->assertMissing(PICK.' .fi-data-grid-selection-indicator')
        ->assertNoJavaScriptErrors();
});

it('works inside a slide-over action', function (): void {
    $slideOver = '.fi-modal-window [id$=".job_ids"]';

    pickJobs('?count=12')
        ->click('Add jobs')
        ->assertVisible($slideOver.' .fi-data-grid-select-all')
        ->click($slideOver.' .fi-data-grid-selection-checkbox[data-key="job-2"]')
        ->assertSeeIn($slideOver, '1 record selected')
        ->click($slideOver.' .fi-data-grid-filters-trigger')
        ->select($slideOver.' .fi-ta-filters select', 'Garland')
        ->click($slideOver.' .fi-data-grid-filters-apply')
        ->click($slideOver.' .fi-data-grid-select-all')
        ->click($slideOver.' .fi-data-grid-select-all-matching')
        ->assertSeeIn($slideOver, '4 records selected')
        ->click('.fi-modal-window .fi-modal-footer button[type="submit"]')
        ->assertScript(savedState('added'), ['job-2', 'job-1', 'job-6', 'job-11'])
        ->assertNoJavaScriptErrors();
});
