<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\Support\IconHeading;
use Workbench\App\Filament\Pages\DeliveryTableDemo;

it('puts the icon before an escaped heading', function (): void {
    $html = IconHeading::make('Orders <b>', Heroicon::OutlinedTruck)->toHtml();

    expect($html)->toStartWith('<svg')
        ->toContain('fi-data-grid-heading-icon')
        ->toEndWith('Orders &lt;b&gt;');
});

it('keeps an html heading as it is', function (): void {
    expect(IconHeading::make(new HtmlString('<em>Orders</em>'), Heroicon::OutlinedTruck)->toHtml())->toEndWith('<em>Orders</em>');
});

it('shows the icon in a Filament table heading', function (): void {
    Filament::setCurrentPanel('demo');

    expect(Livewire::test(DeliveryTableDemo::class)->html())
        ->toMatch('/class="fi-ta-header-heading"[^>]*>\s*<svg[^>]*fi-data-grid-heading-icon/');
});
