<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Livewire\Livewire;
use Workbench\App\Filament\Pages\DeliveryTableDemo;
use Workbench\App\Livewire\ShiftMovementsTable;

it('draws a table header with the markup of a Filament section header', function (): void {
    Filament::setCurrentPanel('demo');

    expect(preg_replace('/<!--.*?-->/s', '', Livewire::test(DeliveryTableDemo::class)->html()))
        ->toMatch('/class="fi-ta-header fi-data-grid-icon-header"[^>]*>\s*<header class="fi-section-header">\s*<svg[^>]*fi-icon[^>]*fi-size-lg[^>]*>.*?<\/svg>\s*<div class="fi-section-header-content fi-section-header-content-has-heading">\s*<div class="fi-section-header-text-ctn">\s*<h2 class="fi-section-header-heading">/s')
        ->not->toContain('fi-ta-header-heading');
});

it('draws the header of a plain Filament table with rows', function (): void {
    Filament::setCurrentPanel('demo');

    expect(Livewire::test(ShiftMovementsTable::class, ['method' => 'card', 'withHeading' => true])->html())
        ->toContain('fi-data-grid-icon-header')
        ->toContain(e(__('demo::demo.shift.sections.movements')))
        ->toContain('fi-ta-table');

    expect(Livewire::test(ShiftMovementsTable::class, ['method' => 'card'])->html())
        ->not->toContain('fi-data-grid-icon-header');
});
