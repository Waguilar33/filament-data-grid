<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class FilamentDataGridServiceProvider extends PackageServiceProvider
{
    public const NAME = 'filament-data-grid';

    public const PACKAGE = 'waguilar33/filament-data-grid';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::NAME)
            ->hasViews(self::NAME)
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('data-grid', __DIR__.'/../resources/dist/data-grid.js'),
            Css::make('data-grid', __DIR__.'/../resources/dist/data-grid.css')->loadedOnRequest(),
        ], self::PACKAGE);
    }
}
