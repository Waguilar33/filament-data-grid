<?php

declare(strict_types=1);

const COUNTS = '[id="form.counts"]';

const COUNTED = 2;

function countedInput(int $row): string
{
    return cell($row, COUNTED, COUNTS).' input';
}

it('shows stored numbers through the field mask', function (): void {
    visitDemo()
        ->assertSeeIn(cell(0, COUNTED, COUNTS), '1,500')
        ->assertSeeIn(cell(1, COUNTED, COUNTS), '250.752')
        ->assertNoJavaScriptErrors();
});

it('masks digits typed on a focused cell and stores the plain number on enter', function (): void {
    $page = visitDemo()
        ->click(cell(0, COUNTED, COUNTS))
        ->assertValue(countedInput(0), '1,500')
        ->keys(countedInput(0), 'Escape')
        ->keys(COUNTS.' .slick-cell.active', '1')
        ->keys(countedInput(0), ['2', '3', '4', '5', '6', '7'])
        ->assertValue(countedInput(0), '1,234,567')
        ->keys(countedInput(0), 'Enter');

    $page->assertSeeIn(cell(0, COUNTED, COUNTS), '1,234,567')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[0].counted', 1234567)
        ->assertNoJavaScriptErrors();
});

it('keeps a number finer than the mask precision when a cell is opened and closed', function (): void {
    visitDemo()
        ->click(cell(1, COUNTED, COUNTS))
        ->assertValue(countedInput(1), '250.752')
        ->assertScript(sprintf('(() => { const input = document.querySelector(%s); return input.selectionStart === 0 && input.selectionEnd === input.value.length })()', json_encode(countedInput(1))), true)
        ->keys(countedInput(1), 'Enter')
        ->click(cell(0, 1, COUNTS))
        ->keys(cell(0, 1, COUNTS).' input', 'Escape')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[1].counted', 250.7525);
});

it('commits a masked value with tab', function (): void {
    visitDemo()
        ->click(cell(1, COUNTED, COUNTS))
        ->keys(countedInput(1), ['9', '8', '7', '6', '.', '5'])
        ->assertValue(countedInput(1), '9,876.5')
        ->keys(countedInput(1), 'Tab')
        ->assertSeeIn(cell(1, COUNTED, COUNTS), '9,876.5')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[1].counted', 9876.5);
});

it('masks a pasted value and strips its separators before saving', function (): void {
    visitDemo()
        ->click(cell(0, COUNTED, COUNTS))
        ->type(countedInput(0), '1,234.5')
        ->assertValue(countedInput(0), '1,234.5')
        ->keys(countedInput(0), 'Enter')
        ->assertSeeIn(cell(0, COUNTED, COUNTS), '1,234.5')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[0].counted', 1234.5);
});

it('checks the unmasked number against the maximum', function (): void {
    visitDemo()
        ->click(cell(0, COUNTED, COUNTS))
        ->keys(countedInput(0), ['2', '0', '0', '0', '0', '0', '0', '0'])
        ->assertValue(countedInput(0), '20,000,000')
        ->keys(countedInput(0), 'Enter')
        ->assertAttribute(cell(0, COUNTED, COUNTS), 'title', 'The Counted field must not be greater than 10000000.')
        ->assertPresent(countedInput(0));
});

it('follows a mask with a comma as the decimal separator', function (): void {
    visitDemo('?locale=es')
        ->assertSeeIn(cell(0, COUNTED, COUNTS), '1.500')
        ->assertSeeIn(cell(1, COUNTED, COUNTS), '250,752')
        ->click(cell(1, COUNTED, COUNTS))
        ->assertValue(countedInput(1), '250,752')
        ->keys(countedInput(1), ['1', '2', '3', '4', ',', '5'])
        ->assertValue(countedInput(1), '1.234,5')
        ->keys(countedInput(1), 'Enter')
        ->assertSeeIn(cell(1, COUNTED, COUNTS), '1.234,5')
        ->click('Guardar')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[1].counted', 1234.5);
});

it('checks the unmasked number against the minimum', function (): void {
    visitDemo()
        ->click(cell(1, COUNTED, COUNTS))
        ->keys(countedInput(1), ['-', '5'])
        ->assertValue(countedInput(1), '-5')
        ->keys(countedInput(1), 'Enter')
        ->assertAttribute(cell(1, COUNTED, COUNTS), 'title', 'The Counted field must be at least 0.')
        ->assertPresent(countedInput(1));
});
