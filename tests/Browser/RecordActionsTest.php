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
        ->assertScript(GRID_SETTLED)
        ->click(orderLineRow('Wild Bread').' .fi-dropdown-trigger')
        ->assertVisible('.fi-dropdown-panel:not([style*="display: none"])')
        ->assertScript('document.getAnimations().length === 0');

    $clipped = $page->script(<<<'JS'
        () => {
            const item = [...document.querySelectorAll('.fi-dropdown-panel')].find((panel) => panel.style.display !== 'none').querySelector('.fi-dropdown-list-item')
            const rect = item.getBoundingClientRect()

            return document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2)?.closest('.fi-dropdown-list-item') !== item
        }
        JS);

    expect($clipped)->toBeFalse()
        ->and($page->script(openDropdownLift('.fi-data-grid-record-actions')))->toBe('30/30')
        ->and($page->script(sprintf('getComputedStyle(document.querySelector(%s)).zIndex', json_encode(ORDER_LINES.' .fi-data-grid-entry-content'))))->toBe('3');
});

it('opens the record when a row with a recordUrl() is clicked', function (): void {
    $page = visit('/demo/order-lines-demo')->waitForEvent('networkidle')->assertScript(GRID_SETTLED);

    expect($page->script(sprintf('document.querySelectorAll(%s).length', json_encode(ORDER_LINES.' .fi-data-grid-record-link'))))->toBeGreaterThan(0);

    $page->click(orderLineRow('Wild Bread').' .fi-data-grid-entry >> nth=0')
        ->assertScript('location.hash !== ""');

    expect($page->script('location.hash'))->toMatch('/^#line-\d+$/');
});

it('keeps record actions from opening the record of a row with a recordUrl()', function (): void {
    $page = visit('/demo/order-lines-demo')->waitForEvent('networkidle')
        ->assertScript(GRID_SETTLED)
        ->click(orderLineRow('Rustic Tea').' button:has-text("Adjust")')
        ->assertSee('Receipt');

    expect($page->script('location.hash'))->toBe('');
});

it('opens the record of a row in a new tab on a ctrl-click', function (): void {
    $page = visit('/demo/order-lines-demo')->waitForEvent('networkidle')->assertScript(GRID_SETTLED);

    $page->script('() => { window.__opened = []; window.open = (url, target) => { window.__opened.push([url, target]) } }');
    $page->script(sprintf(
        '() => document.querySelector(%s).dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, ctrlKey: true }))',
        json_encode(ORDER_LINES.' .fi-data-grid-parent-row .slick-cell.fi-data-grid-entry'),
    ));

    expect($page->script('window.__opened'))->toHaveCount(1)
        ->and($page->script('window.__opened[0][0]'))->toMatch('/#line-\d+$/')
        ->and($page->script('window.__opened[0][1]'))->toBe('_blank')
        ->and($page->script('location.hash'))->toBe('');
});

it('insets record actions and child record actions from the grid edge like the header', function (string $query): void {
    $page = visit('/demo/actions-inset-demo'.$query)->resize(1440, 900)->waitForEvent('networkidle')
        ->assertScript(GRID_SETTLED)
        ->click('[id$="loads"] .fi-data-grid-parent-row:has-text("Dock A") .fi-data-grid-row-toggle')
        ->assertPresent('[id$="loads"] .fi-data-grid-child-actions-cell');

    $insets = $page->script(<<<'JS'
        () => {
            const gap = (outer, inner) => Math.round(outer.getBoundingClientRect().right - inner.getBoundingClientRect().right)
            const padding = (element) => Math.round(parseFloat(getComputedStyle(element).paddingInlineEnd))
            const parents = document.querySelector('[id$="places"] .fi-data-grid-canvas')
            const childRow = document.querySelector('[id$="loads"] .fi-data-grid-child-row:not(.fi-data-grid-child-header)')
            const childHeader = document.querySelector('[id$="loads"] .fi-data-grid-child-header')

            return {
                header: padding([...parents.querySelectorAll('.slick-header-column')].at(-1)),
                actions: [...parents.querySelectorAll('.fi-data-grid-record-actions')].map((actions) => gap(parents, actions)),
                childHeader: padding([...childHeader.querySelectorAll('.fi-data-grid-child-cell')].at(-1)),
                childActions: gap(childRow.querySelector('.fi-data-grid-child-actions-cell'), childRow.querySelector('.fi-data-grid-record-actions')),
            }
        }
        JS);

    $edge = str_contains($query, 'compact') ? 16 : 24;

    expect($insets['header'])->toBe($edge)
        ->and($insets['actions'])->toBe([$edge, $edge])
        ->and($insets['childHeader'])->toBe($edge)
        ->and($insets['childActions'])->toBe($edge);
})->with(['default' => '', 'compact' => '?compact=1', 'dense' => '?dense=1', 'compact and dense' => '?compact=1&dense=1']);
