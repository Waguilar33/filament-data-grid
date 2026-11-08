<?php

declare(strict_types=1);

use Livewire\Livewire;
use Workbench\App\Filament\Pages\OrderLinesDemo;
use Workbench\App\Filament\Resources\DemoOrders\Pages\ViewDemoOrder;
use Workbench\App\Filament\Resources\DemoOrders\RelationManagers\LinesRelationManager;
use Workbench\App\Models\DemoOrder;

it('draws the action modals of a grid in a relation manager without a table', function (): void {
    $order = DemoOrder::query()->create(['reference' => 'ORD-0042']);
    $order->lines()->create(['product' => 'Tea', 'quantity' => 2]);

    $html = Livewire::test(LinesRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewDemoOrder::class])->html();

    expect(substr_count($html, 'filamentActionModals('))->toBe(1);
});

it('draws the action modals of a page with a grid only once', function (): void {
    expect(substr_count(Livewire::test(OrderLinesDemo::class)->html(), 'filamentActionModals('))->toBe(1);
});
