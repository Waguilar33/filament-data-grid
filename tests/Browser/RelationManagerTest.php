<?php

declare(strict_types=1);

use Workbench\App\Models\DemoOrder;

it('opens and submits a record action modal from a grid in a relation manager without a table', function (): void {
    $order = DemoOrder::query()->create(['reference' => 'ORD-0042']);
    $line = $order->lines()->create(['product' => 'Rustic Tea 100 g', 'quantity' => 2]);

    $page = visit("/demo/demo-orders/{$order->getKey()}")->waitForEvent('networkidle')->wait(0.5)
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
