@php
    use Filament\Support\Facades\FilamentAsset;
    $rows = [
        ['id' => 1, 'product' => 'product:12', 'product_label' => 'Golden Tea 100 g', 'quantity' => 2],
        ['id' => 2, 'product' => null, 'product_label' => null, 'quantity' => 1],
    ];
@endphp
<div class="grid gap-6">
    <section>
        <h3 class="text-sm font-semibold mb-2">SlickGrid-Universal</h3>
        <div
            wire:ignore
            x-load
            x-load-css="@js([FilamentAsset::getStyleHref('spike-slickgrid', 'workbench')])"
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('spike-slickgrid', 'workbench') }}"
            x-data="spikeSlickgrid({ rows: @js($rows) })"
        ><div x-ref="grid" style="width: 100%"></div></div>
    </section>
    <section>
        <h3 class="text-sm font-semibold mb-2">Tabulator</h3>
        <div
            wire:ignore
            x-load
            x-load-css="@js([FilamentAsset::getStyleHref('spike-tabulator', 'workbench')])"
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('spike-tabulator', 'workbench') }}"
            x-data="spikeTabulator({ rows: @js($rows) })"
        ><div x-ref="grid"></div></div>
    </section>
</div>
