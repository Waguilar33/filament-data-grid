<?php

declare(strict_types=1);

const ORDER_LINES = '[id="content.lines"]';

function orderLineRow(string $product): string
{
    return sprintf('%s .fi-data-grid-parent-row:has-text("%s")', ORDER_LINES, $product);
}

it('shows each row only the record actions it may use', function (): void {
    visit('/demo/order-lines-demo')->waitForEvent('networkidle')
        ->assertSeeIn(orderLineRow('Rustic Tea'), 'Adjust')
        ->assertSeeIn(orderLineRow('Classic Salsa'), 'Adjust')
        ->assertDontSeeIn(orderLineRow('Wild Bread'), 'Adjust')
        ->assertNoJavaScriptErrors();
});

it('shows grouped record actions in a menu that is not clipped by the grid', function (): void {
    $page = visit('/demo/order-lines-demo')->waitForEvent('networkidle')
        ->wait(0.5)
        ->click(orderLineRow('Wild Bread').' .fi-dropdown-trigger')
        ->wait(0.5)
        ->assertVisible('.fi-dropdown-panel:not([style*="display: none"])');

    $clipped = $page->script(<<<'JS'
        () => {
            const item = [...document.querySelectorAll('.fi-dropdown-panel')].find((panel) => panel.style.display !== 'none').querySelector('.fi-dropdown-list-item')
            const rect = item.getBoundingClientRect()

            return document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2)?.closest('.fi-dropdown-list-item') !== item
        }
        JS);

    expect($clipped)->toBeFalse();
});
