<?php

declare(strict_types=1);

use Waguilar33\FilamentDataGrid\Tests\Fixtures\OrderForm;
use Workbench\App\Models\DemoOrder;

use function Pest\Livewire\livewire;

it('loads, edits and saves rows through a relationship', function (): void {
    $order = DemoOrder::create(['reference' => 'A-1']);
    $tea = $order->lines()->create(['product' => 'Tea', 'quantity' => 2, 'sort' => 0]);
    $order->lines()->create(['product' => 'Coffee', 'quantity' => 1, 'sort' => 1]);

    $component = livewire(OrderForm::class, ['order' => $order]);
    $keys = rowKeys($component);

    expect($keys)->toBe(["record-{$tea->id}", 'record-'.($tea->id + 1)]);

    $component
        ->set("data.lines.record-{$tea->id}.quantity", 5)
        ->set('data.lines', array_reverse($component->get('data.lines'), preserve_keys: true))
        ->call('save')
        ->assertHasNoErrors();

    expect($order->lines()->get()->map->only(['product', 'quantity', 'sort'])->all())->toEqual([
        ['product' => 'Coffee', 'quantity' => '1.000', 'sort' => 1],
        ['product' => 'Tea', 'quantity' => '5.000', 'sort' => 2],
    ]);
});
