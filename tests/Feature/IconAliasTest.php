<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;
use Workbench\App\Filament\Pages\DeliveryTableDemo;
use Workbench\App\Filament\Pages\DispatchDemo;

it('lets an app replace the plugin icons through Filament icon aliases', function (): void {
    Filament::setCurrentPanel('demo');

    FilamentIcon::register([
        DataGridIconAlias::ROW_EXPAND => new HtmlString('<i data-icon="expand"></i>'),
        DataGridIconAlias::ROW_OPEN => new HtmlString('<i data-icon="open"></i>'),
    ]);

    expect(Livewire::test(DeliveryTableDemo::class)->html())
        ->toMatch('/data-icon=\S{0,12}expand/')
        ->toMatch('/data-icon=\S{0,12}open/');
});

it('uses the reorder, date, edit and expand aliases in the editable grid', function (): void {
    Filament::setCurrentPanel('demo');

    FilamentIcon::register([
        DataGridIconAlias::CELL_REORDER => new HtmlString('<i data-icon="reorder"></i>'),
        DataGridIconAlias::CELL_DATE => new HtmlString('<i data-icon="date"></i>'),
        DataGridIconAlias::CELL_EDIT => new HtmlString('<i data-icon="edit"></i>'),
    ]);

    expect(Livewire::test(DispatchDemo::class)->html())
        ->toMatch('/data-icon=\S{0,12}reorder/')
        ->toMatch('/data-icon=\S{0,12}date/')
        ->toMatch('/data-icon=\S{0,12}edit/');
});
