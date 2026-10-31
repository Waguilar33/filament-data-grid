<?php

declare(strict_types=1);

const RECEIPTS = '[id="form.receipts"]';

function headerHasPencil(string $grid, int $column): string
{
    return sprintf('Boolean(document.querySelector(%s).querySelectorAll(".slick-header-column")[%d].querySelector(".fi-data-grid-header-edit svg"))', json_encode($grid), $column);
}

function computed(string $selector, string $property): string
{
    return sprintf('getComputedStyle(document.querySelector(%s)).%s', json_encode($selector), $property);
}

it('underlines editable values and leaves locked ones plain', function (): void {
    visitDemo()
        ->assertScript(computed(cell(0, 0, RECEIPTS).' .fi-data-grid-editable-value', 'borderBottomStyle'), 'dashed')
        ->assertSeeIn(cell(0, 0, RECEIPTS), 'Scan or type')
        ->assertNotPresent(cell(0, 1, RECEIPTS).' .fi-data-grid-editable-value')
        ->assertNotPresent(cell(0, 2, RECEIPTS).' .fi-data-grid-editable-value')
        ->assertPresent(cell(0, COUNTED, COUNTS).' .fi-data-grid-editable-value')
        ->assertNoJavaScriptErrors();
});

it('turns the underline solid on hover', function (): void {
    visitDemo()
        ->hover(cell(1, COUNTED, COUNTS))
        ->assertScript(computed(cell(1, COUNTED, COUNTS).' .fi-data-grid-editable-value', 'borderBottomStyle'), 'solid');
});

it('marks only columns that are editable in some row with a pencil', function (): void {
    visitDemo()
        ->assertScript(headerHasPencil(RECEIPTS, 0), true)
        ->assertScript(headerHasPencil(RECEIPTS, 1), false)
        ->assertScript(headerHasPencil(RECEIPTS, 2), false);
});

it('keeps the pencil inside an end-aligned header', function (): void {
    visitDemo()->assertScript(sprintf(<<<'JS'
        (() => {
            const header = [...document.querySelector(%s).querySelectorAll('.slick-header-column')].find((column) => column.textContent.includes('Counted'))
            const pencil = header.querySelector('.fi-data-grid-header-edit').getBoundingClientRect()

            return pencil.right <= header.getBoundingClientRect().right && pencil.width > 0 && pencil.width <= 14.5
        })()
        JS, json_encode(COUNTS)), true);
});

it('adds the pencil when a live change makes a column editable, without closing the open editor', function (): void {
    visitDemo()
        ->click(cell(0, 0, RECEIPTS))
        ->keys(cell(0, 0, RECEIPTS).' input', ['R', 'C', '-', '1', 'Enter'])
        ->wait(1.5)
        ->assertScript(headerHasPencil(RECEIPTS, 1), true)
        ->assertPresent(cell(1, 0, RECEIPTS).' input')
        ->assertPresent(cell(0, 1, RECEIPTS).' .fi-data-grid-editable-value');
});

it('lightens editable cells of highlighted columns only', function (): void {
    $page = visitDemo()
        ->click(cell(0, 0, RECEIPTS))
        ->keys(cell(0, 0, RECEIPTS).' input', ['R', 'C', '-', '1', 'Enter'])
        ->wait(1.5);

    $transparent = 'rgba(0, 0, 0, 0)';

    expect($page->script(computed(cell(0, 1, RECEIPTS), 'backgroundColor')))->not->toBe($transparent)
        ->and($page->script(computed(cell(1, 1, RECEIPTS), 'backgroundColor')))->toBe($transparent)
        ->and($page->script(computed(cell(0, 2, RECEIPTS), 'backgroundColor')))->toBe($transparent)
        ->and($page->script(computed(cell(1, COUNTED, COUNTS), 'backgroundColor')))->toBe($transparent);
});

it('shows a field’s suffix icon after editable values only', function (): void {
    visitDemo()
        ->assertPresent(cell(0, 0, RECEIPTS).' .fi-data-grid-cell-icon svg')
        ->assertNotPresent(cell(0, 1, RECEIPTS).' .fi-data-grid-cell-icon')
        ->assertNotPresent(cell(0, 1).' .fi-data-grid-cell-icon');
});

it('steps a masked number with the editor’s buttons and keeps typing focus', function (): void {
    visitDemo()
        ->click(cell(0, COUNTED, COUNTS))
        ->click(cell(0, COUNTED, COUNTS).' .fi-data-grid-stepper-up')
        ->assertValue(countedInput(0), '1,501')
        ->assertScript(sprintf('document.activeElement === document.querySelector(%s)', json_encode(countedInput(0))), true)
        ->click(cell(0, COUNTED, COUNTS).' .fi-data-grid-stepper-down')
        ->click(cell(0, COUNTED, COUNTS).' .fi-data-grid-stepper-down')
        ->assertValue(countedInput(0), '1,499')
        ->keys(countedInput(0), 'Tab')
        ->assertSeeIn(cell(0, COUNTED, COUNTS), '1,499')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[0].counted', 1499)
        ->assertNoJavaScriptErrors();
});

it('steps an unmasked number with the editor’s buttons within its minimum', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->assertAttribute(cell(0, 1).' .fi-data-grid-stepper-up', 'aria-label', 'Increase')
        ->click(cell(0, 1).' .fi-data-grid-stepper-down')
        ->click(cell(0, 1).' .fi-data-grid-stepper-down')
        ->assertValue(cell(0, 1).' input', '1');
});
