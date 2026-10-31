<?php

declare(strict_types=1);

function quantityInput(int $row): string
{
    return cell($row, 1).' input';
}

it('steps an unmasked number cell with the arrow keys without leaving the row', function (): void {
    visitDemo()
        ->click(cell(1, 1))
        ->assertValue(quantityInput(1), '1.5')
        ->keys(quantityInput(1), ['ArrowUp', 'ArrowUp'])
        ->assertValue(quantityInput(1), '3.5')
        ->keys(quantityInput(1), 'ArrowDown')
        ->assertValue(quantityInput(1), '2.5')
        ->assertScript('document.activeElement.closest(".slick-row").style.top', '44px')
        ->keys(quantityInput(1), 'Enter')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState().'[1].quantity', 2.5)
        ->assertNoJavaScriptErrors();
});

it('stops an arrow step at the minimum', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->assertValue(quantityInput(0), '2')
        ->keys(quantityInput(0), ['ArrowDown', 'ArrowDown', 'ArrowDown'])
        ->assertValue(quantityInput(0), '1');
});

it('leaves modified arrow keys alone', function (): void {
    visitDemo()
        ->click(cell(0, 1))
        ->keys(quantityInput(0), ['Shift+ArrowUp', 'Alt+ArrowUp'])
        ->assertValue(quantityInput(0), '2');
});

it('steps a masked number cell from the number it shows and keeps the mask', function (): void {
    visitDemo()
        ->click(cell(0, COUNTED, COUNTS))
        ->assertValue(countedInput(0), '1,500')
        ->keys(countedInput(0), 'ArrowUp')
        ->assertValue(countedInput(0), '1,501')
        ->keys(countedInput(0), 'Enter')
        ->assertSeeIn(cell(0, COUNTED, COUNTS), '1,501')
        ->click(cell(1, COUNTED, COUNTS))
        ->keys(countedInput(1), 'ArrowDown')
        ->assertValue(countedInput(1), '249.752')
        ->keys(countedInput(1), 'Enter')
        ->click('Save')
        ->wait(1.5)
        ->assertScript(savedState('counts').'[0].counted', 1501)
        ->assertScript(savedState('counts').'[1].counted', 249.752)
        ->assertNoJavaScriptErrors();
});

it('stops a masked arrow step at the maximum', function (): void {
    visitDemo()
        ->click(cell(0, COUNTED, COUNTS))
        ->type(countedInput(0), '9,999,999.5')
        ->keys(countedInput(0), 'ArrowUp')
        ->assertValue(countedInput(0), '10,000,000');
});

it('steps a masked number cell with a comma as the decimal separator', function (): void {
    visitDemo('?locale=es')
        ->click(cell(1, COUNTED, COUNTS))
        ->assertValue(countedInput(1), '250,752')
        ->keys(countedInput(1), 'ArrowUp')
        ->assertValue(countedInput(1), '251,752')
        ->click(cell(0, COUNTED, COUNTS))
        ->keys(countedInput(0), 'ArrowUp')
        ->assertValue(countedInput(0), '1.501');
});
