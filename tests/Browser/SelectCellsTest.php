<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const SELECT_CELLS = '[id="form.lines"]';

const OPEN_PANEL = '.fi-dropdown-panel:not([style*="display: none"])';

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

function startSlowPick(): string
{
    return <<<'JS'
        const rows = () => [...document.querySelectorAll('[id="form.lines"] .slick-row')].sort((a, b) => parseFloat(a.style.top) - parseFloat(b.style.top))
        const button = (row) => rows()[row].querySelector('.slick-cell.l0 .fi-select-input-btn')
        const frames = () => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))
        const fetch = window.fetch
        let pending = 0
        let finished = 0
        window.fetch = async (...args) => {
            pending++
            try {
                return await fetch(...args)
            } finally {
                finished++
            }
        }
        const landed = async () => {
            for (let tries = 0; (pending === 0 || finished < pending) && tries < 60; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
            await new Promise((resolve) => setTimeout(resolve, 100))
            await frames()
            return pending > 0 && finished === pending
        }
        button(0).dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
        button(0).click()
        await frames()
        button(0).closest('.fi-data-grid-select').querySelector('[role="option"]').click()
        await frames()
        JS;
}

it('opens a select clicked while a slow request lands between mousedown and click', function (): void {
    $script = startSlowPick();

    $opened = selectCellsDemo()->script(<<<JS
        async () => {
            {$script}
            const target = button(1)
            target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
            target.focus()
            const hasLanded = await landed()
            target.click()
            await frames()

            return [hasLanded, target.isConnected, target.getAttribute('aria-expanded')]
        }
        JS);

    expect($opened)->toBe([true, true, 'true']);
});

it('keeps a select open while a slow request lands', function (): void {
    $script = startSlowPick();

    $state = selectCellsDemo()->script(<<<JS
        async () => {
            {$script}
            const target = button(1)
            target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
            target.click()
            await frames()
            const wasOpen = target.getAttribute('aria-expanded')
            const hasLanded = await landed()

            return [wasOpen, hasLanded, target.isConnected, target.getAttribute('aria-expanded'), rows()[0].querySelector('.slick-cell.l2').textContent.trim()]
        }
        JS);

    expect($state)->toBe(['true', true, true, 'true', 'Picked p1']);
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
