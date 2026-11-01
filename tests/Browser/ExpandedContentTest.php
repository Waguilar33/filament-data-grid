<?php

declare(strict_types=1);

function dispatchHost(string $text): string
{
    return sprintf('[id="form.products"] .fi-data-grid-child-host:has-text("%s")', $text);
}

it('opens a row that has only content below, with no nested grid', function (): void {
    $page = visitDispatch()
        ->assertSeeIn(dispatchHost('PO-2026-0003'), 'The stock arrives when purchase order PO-2026-0003 is received.')
        ->assertCount(dispatchHost('PO-2026-0003').' .fi-data-grid-canvas', 0)
        ->assertCount('[id="form.products"] .grid-canvas .slick-row:has-text("Standing desk") .fi-data-grid-row-toggle[aria-expanded="true"]', 1);

    $fits = $page->script(<<<'JS'
        () => {
            const host = [...document.querySelectorAll('[id="form.products"] .fi-data-grid-child-host')].find((node) => node.textContent.includes('PO-2026-0003'))
            const text = host.querySelector('.fi-data-grid-child-host-slot-below').getBoundingClientRect()
            const box = host.getBoundingClientRect()

            return text.height > 0 && text.bottom <= box.bottom && text.top >= box.top
        }
        JS);

    expect($fits)->toBeTrue();
    $page->assertNoJavaScriptErrors();
});

it('shows content above a nested grid in the same panel', function (): void {
    $page = visitDispatch()->assertSeeIn(dispatchHost('EM-4471'), 'Scan or type the code of each unit.');

    $order = $page->script(<<<'JS'
        () => {
            const host = [...document.querySelectorAll('[id="form.products"] .fi-data-grid-child-host')].find((node) => node.textContent.includes('EM-4471'))

            return [...host.children].filter((node) => !node.hidden).map((node) => node.className.includes('slot-above') ? 'above' : node.className.includes('canvas') ? 'grid' : 'other')
        }
        JS);

    expect(array_slice($order, 0, 2))->toBe(['above', 'grid'])
        ->and($page->script("() => [...document.querySelectorAll('[id=\"form.products\"] .fi-data-grid-child-host')].filter((node) => node.textContent.includes('Olive oil') || node.textContent.includes('North · Aisle 2')).every((node) => !node.textContent.includes('Scan or type'))"))->toBeTrue();
});

it('keeps the content and the rows below in place when a row closes and opens again', function (): void {
    $page = visitDispatch()
        ->click('[id="form.products"] .grid-canvas .slick-row:has-text("Standing desk") .fi-data-grid-row-toggle')
        ->wait(0.3)
        ->assertCount(dispatchHost('PO-2026-0003'), 0)
        ->click('[id="form.products"] .grid-canvas .slick-row:has-text("Standing desk") .fi-data-grid-row-toggle')
        ->wait(0.3)
        ->assertSeeIn(dispatchHost('PO-2026-0003'), 'PO-2026-0003');

    $gap = $page->script(<<<'JS'
        () => {
            const row = [...document.querySelectorAll('[id="form.products"] .grid-canvas .slick-row')].find((node) => node.textContent.includes('Standing desk'))
            const placeholder = row.nextElementSibling
            const host = [...document.querySelectorAll('[id="form.products"] .fi-data-grid-child-host')].find((node) => node.textContent.includes('PO-2026-0003'))

            return Math.round(host.getBoundingClientRect().bottom - placeholder.getBoundingClientRect().bottom)
        }
        JS);

    expect(abs($gap))->toBeLessThanOrEqual(16);
});

it('shows content without a nested grid straight in the open row, without the panel', function (): void {
    $styles = visitDispatch()->script(<<<'JS'
        () => {
            const style = (text) => {
                const host = [...document.querySelectorAll('[id="form.products"] .fi-data-grid-child-host')].find((node) => node.textContent.includes(text))
                const computed = getComputedStyle(host)
                const slots = [...host.querySelectorAll('.fi-data-grid-child-host-slot')].filter((slot) => !slot.hidden)

                return [computed.boxShadow, computed.backgroundColor, computed.borderTopLeftRadius, slots.map((slot) => getComputedStyle(slot).paddingTop)]
            }

            return { bare: style('PO-2026-0003'), panel: style('EM-4471') }
        }
        JS);

    expect($styles['bare'])->toBe(['none', 'rgba(0, 0, 0, 0)', '0px', ['0px']])
        ->and($styles['panel'][0])->not->toBe('none')
        ->and($styles['panel'][2])->not->toBe('0px')
        ->and($styles['panel'][3][0])->not->toBe('0px');
});
