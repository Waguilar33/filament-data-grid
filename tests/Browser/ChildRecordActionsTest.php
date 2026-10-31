<?php

declare(strict_types=1);

const PAYMENTS_GRID = '[id="form.payments_grid"]';

function paymentGroup(string $method): string
{
    return sprintf('%s .fi-data-grid-parent-row:has-text("%s")', PAYMENTS_GRID, $method);
}

function paymentRow(string $reference): string
{
    return sprintf('%s .fi-data-grid-child-row:has-text("%s")', PAYMENTS_GRID, $reference);
}

it('deletes child rows without a modal and drops an emptied group', function (): void {
    visit('/demo/payments-demo')->waitForEvent('networkidle')
        ->click(paymentGroup('Card').' .fi-data-grid-row-toggle')
        ->click(paymentGroup('Cash').' .fi-data-grid-row-toggle')
        ->wait(0.3)
        ->click(paymentRow('Till 1').' button:has-text("Delete")')
        ->wait(0.8)
        ->assertDontSee('Till 1')
        ->assertVisible(paymentRow('Till 2'))
        ->assertSeeIn(paymentGroup('Cash'), '50')
        ->click(paymentRow('VISA 4242').' button:has-text("Delete")')
        ->wait(0.8)
        ->assertDontSee('VISA 4242')
        ->assertCount(PAYMENTS_GRID.' .fi-data-grid-parent-row', 1)
        ->assertSee('Total paid')
        ->assertNoJavaScriptErrors();
});

it('lines up the child action buttons at the end of each child row', function (): void {
    $page = visit('/demo/payments-demo')->waitForEvent('networkidle')
        ->click(paymentGroup('Cash').' .fi-data-grid-row-toggle')
        ->wait(0.3);

    $gaps = $page->script(<<<'JS'
        () => [...document.querySelectorAll('[id="form.payments_grid"] .fi-data-grid-child-row:not(.fi-data-grid-child-header)')].map((row) => {
            const actions = row.querySelector('.fi-data-grid-record-actions').getBoundingClientRect()
            const cell = row.querySelector('.fi-data-grid-child-actions-cell').getBoundingClientRect()

            return [Math.round(cell.right - actions.right), actions.width > 0, actions.right <= row.getBoundingClientRect().right]
        })
        JS);

    expect($gaps)->toHaveCount(2)
        ->and(array_unique(array_column($gaps, 0)))->toHaveCount(1)
        ->and(array_column($gaps, 1))->each->toBeTrue()
        ->and(array_column($gaps, 2))->each->toBeTrue();
});
