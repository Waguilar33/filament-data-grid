<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

final class FilamentDataGridServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'waguilar33/filament-data-grid';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-data-grid');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-data-grid');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/filament-data-grid'),
        ], 'filament-data-grid-translations');

        FilamentAsset::register([
            AlpineComponent::make('data-grid', __DIR__.'/../resources/dist/data-grid.js'),
            Css::make('data-grid', __DIR__.'/../resources/dist/data-grid.css')->loadedOnRequest(),
        ], self::PACKAGE);
    }
}
