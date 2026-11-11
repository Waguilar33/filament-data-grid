<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const SELECT_CELLS = '[id="form.lines"]';

const OPEN_PANEL = '.fi-dropdown-panel:not([style*="display: none"])';

function styleOf(string $selector, string $property): string
{
    return sprintf('getComputedStyle(document.querySelector(%s)).%s', json_encode($selector), $property);
}

function selectCellsDemo(): AwaitableWebpage
{
    return visit('/demo/select-cells-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);
}

function productButton(int $row): string
{
    return cell($row, 0, SELECT_CELLS).' .fi-select-input-btn';
}

function waitForPayloadChange(): string
{
    return <<<'JS'
        async () => {
            const payload = document.querySelector('[id="form.lines"] [x-ref="payload"]')
            const before = payload.dataset.payload

            for (let tries = 0; payload.dataset.payload === before && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }

            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))

            return payload.dataset.payload !== before
        }
        JS;
}

it('opens a select dropdown flush with its cell, just below it', function (): void {
    $page = selectCellsDemo()->click(productButton(0))->assertVisible(OPEN_PANEL);

    $geometry = $page->script(<<<'JS'
        (() => {
            const panel = document.querySelector('.fi-dropdown-panel:not([style*="display: none"])').getBoundingClientRect()
            const cell = panel && document.querySelector('[id="form.lines"] .slick-cell.l0 .fi-select-input-btn[aria-expanded="true"]').closest('.slick-cell').getBoundingClientRect()

            return [Math.round(panel.left - cell.left), Math.round(panel.width - cell.width), Math.round(panel.top - cell.bottom)]
        })()
        JS);

    expect($geometry[0])->toBeBetween(-1, 1)
        ->and($geometry[1])->toBeBetween(-1, 1)
        ->and($geometry[2])->toBeBetween(0, 6);
});

it('opens a select with its options at once, without asking the server', function (): void {
    $page = selectCellsDemo();

    $result = $page->script(<<<'JS'
        async () => {
            const button = document.querySelector('[id="form.lines"] .slick-cell.l0 .fi-select-input-btn')
            let requests = 0
            const fetch = window.fetch
            window.fetch = (...args) => { requests++; return fetch(...args) }
            button.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
            button.click()
            await new Promise((resolve) => requestAnimationFrame(resolve))
            const panel = button.closest('.fi-data-grid-select').querySelector('.fi-dropdown-panel')
            const visible = [...panel.querySelectorAll('[role="option"]')].filter((option) => option.checkVisibility()).length
            await new Promise((resolve) => setTimeout(resolve, 300))
            window.fetch = fetch

            return [visible, Boolean(panel.querySelector('.fi-select-input-message')?.checkVisibility()), requests]
        }
        JS);

    expect($result)->toBe([50, false, 0]);
});

/**
 * Pest re-runs a script that takes over about a second, so each script here returns at once and waits are polled.
 */
function startSlowPick(): string
{
    return <<<'JS'
        () => {
            const rows = () => [...document.querySelectorAll('[id="form.lines"] .slick-row')].sort((a, b) => parseFloat(a.style.top) - parseFloat(b.style.top))
            window.__button = (row) => rows()[row].querySelector('.slick-cell.l0 .fi-select-input-btn')
            const fetch = window.fetch
            window.__pending = 0
            window.__finished = 0
            window.fetch = async (...args) => {
                window.__pending++
                try {
                    return await fetch(...args)
                } finally {
                    window.__finished++
                }
            }
            window.__button(0).dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
            window.__button(0).click()
            window.__button(0).closest('.fi-data-grid-select').querySelector('[role="option"]').click()
            window.__closes = 0
            const target = window.__button(1)
            target.__tag = true
            window.__target = target
            new MutationObserver(() => target.getAttribute('aria-expanded') === 'false' && window.__closes++).observe(target, { attributes: true, attributeFilter: ['aria-expanded'] })
        }
        JS;
}

const REQUEST_LANDED = 'window.__pending > 0 && window.__finished === window.__pending';

it('opens a select clicked while a slow request lands between mousedown and click', function (): void {
    $page = selectCellsDemo();
    $page->script(startSlowPick());
    $page->script('() => { window.__target.dispatchEvent(new MouseEvent("mousedown", { bubbles: true })); window.__target.focus() }');

    expect($page->script('window.__finished'))->toBe(0);

    $page->assertScript(REQUEST_LANDED)->wait(0.2);
    $page->script('() => window.__target.click()');

    $page->assertScript('window.__target.getAttribute("aria-expanded") === "true"')
        ->assertScript('window.__target.isConnected && window.__target === window.__button(1) && window.__closes === 0')
        ->assertNoJavaScriptErrors();
});

it('keeps a select open while a slow request lands', function (): void {
    $page = selectCellsDemo();
    $page->script(startSlowPick());
    $page->script('() => { window.__target.dispatchEvent(new MouseEvent("mousedown", { bubbles: true })); window.__target.click() }');

    expect($page->script('[window.__finished, window.__target.getAttribute("aria-expanded")]'))->toBe([0, 'true']);

    $page->assertScript(REQUEST_LANDED)->wait(0.2)
        ->assertScript('window.__target.isConnected && window.__target === window.__button(1) && window.__closes === 0 && window.__target.getAttribute("aria-expanded") === "true"')
        ->assertSeeIn(cell(0, 2, SELECT_CELLS), 'Picked p1')
        ->assertNoJavaScriptErrors();
});

it('picks in several rows while slow requests are pending', function (): void {
    $page = selectCellsDemo();

    foreach ([[0, 'Product 3'], [1, 'Product 4'], [2, 'Product 5'], [0, 'Product 6'], [1, 'Product 7']] as [$row, $option]) {
        $page->click(productButton($row))
            ->assertVisible(OPEN_PANEL)
            ->click(OPEN_PANEL.' [role="option"]:has-text("'.$option.'") >> nth=0')
            ->assertSeeIn(cell($row, 0, SELECT_CELLS).' .fi-select-input-value-label', $option);
    }

    $page->assertSeeIn(cell(0, 0, SELECT_CELLS).' .fi-select-input-value-label', 'Product 6')
        ->assertSeeIn(cell(1, 0, SELECT_CELLS).' .fi-select-input-value-label', 'Product 7')
        ->assertSeeIn(cell(2, 0, SELECT_CELLS).' .fi-select-input-value-label', 'Product 5')
        ->wait(2)
        ->assertSeeIn(cell(1, 2, SELECT_CELLS), 'Picked p7')
        ->click('Save')
        ->assertPresent('[data-saved-state]')
        ->assertScript(savedState().'.map((line) => line.product).join()', 'p6,p7,p5')
        ->assertNoJavaScriptErrors();
});

it('draws a picked value as Filament does, on one line and without an underline', function (): void {
    $page = selectCellsDemo()
        ->click(productButton(0))
        ->click(OPEN_PANEL.' [role="option"]:has-text("Product 2") >> nth=0')
        ->assertSeeIn(cell(0, 2, SELECT_CELLS), 'Picked p2')
        ->assertSeeIn(cell(0, 0, SELECT_CELLS).' .fi-select-input-value-label', 'Product 2');

    expect($page->script(styleOf(cell(0, 0, SELECT_CELLS).' .fi-select-input-value-label', 'borderBottomWidth')))->toBe('0px')
        ->and($page->script(styleOf(cell(1, 0, SELECT_CELLS).' .fi-select-input-placeholder', 'borderBottomWidth')))->toBe('0px')
        ->and($page->script(styleOf(cell(2, 1, SELECT_CELLS).' .fi-data-grid-editable-value', 'borderBottomStyle')))->toBe('dashed');

    $page->assertPresent(cell(0, 0, SELECT_CELLS).' .fi-select-input-value-remove-btn');
});

it('shows no scrollbars after a row is added when everything fits', function (): void {
    $page = selectCellsDemo()->click('Add to lines')->assertPresent(cell(3, 0, SELECT_CELLS))->wait(0.5);

    expect($page->script(<<<'JS'
        (() => [...document.querySelectorAll('[id="form.lines"] .slick-viewport')].map((viewport) => [viewport.scrollWidth - viewport.clientWidth, viewport.scrollHeight - viewport.clientHeight]))()
        JS))->each->toBe([0, 0]);
});
