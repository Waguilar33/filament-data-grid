<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::addLocation(__DIR__.'/../../resources/views');

        $dist = __DIR__.'/../../../resources/dist';

        FilamentAsset::register([
            AlpineComponent::make('spike-slickgrid', "{$dist}/spike-slickgrid.js"),
            AlpineComponent::make('spike-tabulator', "{$dist}/spike-tabulator.js"),
            Css::make('spike-slickgrid', "{$dist}/spike-slickgrid.css")->loadedOnRequest(),
            Css::make('spike-tabulator', "{$dist}/spike-tabulator.css")->loadedOnRequest(),
        ], 'workbench');
    }
}
