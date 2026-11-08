<?php

declare(strict_types=1);

use Workbench\App\Models\DemoOrder;

it('opens and submits a record action modal from a grid in a relation manager without a table', function (): void {
    $order = DemoOrder::query()->create(['reference' => 'ORD-0042']);
    $line = $order->lines()->create(['product' => 'Rustic Tea 100 g', 'quantity' => 2]);

    $page = visit("/demo/demo-orders/{$order->getKey()}")->waitForEvent('networkidle')->wait(0.5)
        ->click('.fi-tabs-item:has-text("Lines")')
        ->wait(1)
        ->click('.fi-resource-relation-manager .fi-data-grid-parent-row button:has-text("Adjust")')
        ->wait(1)
        ->assertVisible('.fi-modal-window input')
        ->assertValue('.fi-modal-window input', '2');

    $page->clear('.fi-modal-window input')
        ->type('.fi-modal-window input', '7')
        ->click('.fi-modal-window button[type="submit"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect((float) $line->refresh()->quantity)->toBe(7.0)
        ->and($page->script('document.querySelectorAll(".fi-resource-relation-manager [x-data*=filamentActionModals]").length'))->toBe(1);
});

it('never shows a half laid out grid while a relation manager tab opens', function (): void {
    $order = DemoOrder::query()->create(['reference' => 'ORD-0043']);
    $order->lines()->create(['product' => 'Rustic Tea 100 g', 'quantity' => 2]);
    $order->lines()->create(['product' => 'Wild Bread 100 g with a long name', 'quantity' => 40]);

    $page = visit("/demo/demo-orders/{$order->getKey()}")->waitForEvent('networkidle')->wait(0.5);

    $page->script(<<<'JS'
        () => {
            window.__frames = []

            const sample = () => {
                const canvas = document.querySelector('.fi-resource-relation-manager .fi-data-grid-canvas')

                if (canvas?.querySelector('.slick-header-column') && !canvas.classList.contains('fi-data-grid-measuring')) {
                    const rows = [...canvas.querySelectorAll('.fi-data-grid-parent-row')]
                    const headers = canvas.querySelectorAll('.slick-header-column').length
                    const broken = rows.length === 0 || rows.some((row) => {
                        const cells = [...row.querySelectorAll('.slick-cell')]
                        const wrapped = cells.some((cell) => [...cell.querySelectorAll('.fi-in-text-item')].some((item) => item.getClientRects().length > 1 || item.getBoundingClientRect().height > 30))

                        return cells.length !== headers || cells.some((cell) => cell.getBoundingClientRect().width === 0) || wrapped
                    })

                    window.__frames.push(broken)

                    if (broken) {
                        window.__details = window.__details ?? rows.map((row) => [...row.querySelectorAll('.slick-cell')].map((cell) => [Math.round(cell.getBoundingClientRect().width), Math.round(cell.getBoundingClientRect().height), cell.textContent.trim().slice(0, 20)]))
                        window.__details.push({ headers, width: canvas.clientWidth })
                    }
                }

                if (window.__frames.length < 60) {
                    requestAnimationFrame(sample)
                }
            }

            requestAnimationFrame(sample)
        }
        JS);

    $page->click('.fi-tabs-item:has-text("Lines")')->wait(2);

    $frames = $page->script('window.__frames');

    expect($frames)->not->toBeEmpty()
        ->and($page->script('window.__details ?? null'))->toBeNull();
});
