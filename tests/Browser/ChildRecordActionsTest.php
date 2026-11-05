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
        ->assertCount(PAYMENTS_GRID.' .fi-data-grid-parent-row', 3)
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

it('shows each group only the child columns its own rows use', function (string $method, array $headers, string $value): void {
    $page = visit('/demo/payments-demo')->waitForEvent('networkidle')
        ->click(paymentGroup($method).' .fi-data-grid-row-toggle')
        ->wait(0.3);

    $shown = $page->script(<<<'JS'
        () => {
            const header = document.querySelector('[id="form.payments_grid"] .fi-data-grid-child-header')
            const cells = [...document.querySelectorAll('[id="form.payments_grid"] .fi-data-grid-child-row:not(.fi-data-grid-child-header)')][0]

            return {
                headers: [...header.querySelectorAll('[role="columnheader"]')].map((cell) => cell.textContent.trim()).filter((text) => text !== '' && text !== 'Actions'),
                columns: getComputedStyle(header).gridTemplateColumns.split(' ').length,
                cells: cells.querySelectorAll('[role="cell"]').length,
            }
        }
        JS);

    expect($shown['headers'])->toBe($headers)
        ->and($shown['columns'])->toBe(count($headers) + 1)
        ->and($shown['cells'])->toBe(count($headers) + 1);

    $page->assertSeeIn(PAYMENTS_GRID.' .fi-data-grid-child-row:not(.fi-data-grid-child-header)', $value)
        ->assertNoJavaScriptErrors();
})->with([
    'cash' => ['Cash', ['Amount', 'Reference'], 'Till 1'],
    'card' => ['Card', ['Amount', 'Reference', 'Authorization'], 'A-90311'],
    'transfer' => ['Transfer', ['Amount', 'Reference', 'Bank'], 'Banco Norte'],
]);

it('renders no toggle and no child table for a group without payments, and child tables for the others', function (): void {
    $childMarkup = sprintf(
        'document.querySelectorAll(%s).length',
        json_encode(implode(', ', array_map(
            fn (string $class): string => PAYMENTS_GRID.' '.$class,
            ['.fi-data-grid-child-row', '.fi-data-grid-child-slot', '.fi-data-grid-child-table-host', '.fi-data-grid-child-table-placeholder'],
        ))),
    );

    $page = visit('/demo/payments-demo')->waitForEvent('networkidle')
        ->assertScript($childMarkup, 0)
        ->assertCount(paymentGroup('Voucher').' .fi-data-grid-row-toggle', 0)
        ->click(paymentGroup('Cash').' .fi-data-grid-row-toggle')
        ->click(paymentGroup('Card').' .fi-data-grid-row-toggle')
        ->click(paymentGroup('Transfer').' .fi-data-grid-row-toggle')
        ->click(paymentGroup('Voucher'))
        ->wait(0.3)
        ->assertScript($childMarkup, 7)
        ->assertCount(PAYMENTS_GRID.' .fi-data-grid-child-header', 3);

    $methodCellsLeft = $page->script(sprintf(<<<'JS'
        () => [...document.querySelectorAll(%s)].map((row) => row.querySelector('.slick-cell:has(.fi-in-text-item)').getBoundingClientRect().left)
        JS, json_encode(PAYMENTS_GRID.' .fi-data-grid-parent-row')));

    expect(array_unique($methodCellsLeft))->toHaveCount(1);

    $page->assertNoJavaScriptErrors();
});
