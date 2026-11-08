<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Waguilar\FilamentDataGrid\FilamentDataGridServiceProvider;
use Workbench\App\Providers\Filament\DemoPanelProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    use WithWorkbench;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $worker = getenv('TEST_TOKEN');

        if ($worker === false) {
            return;
        }

        // Laravel 11 and early 12 write compiled views and real-time facades in place, so parallel workers sharing a folder read each other's half-written files.
        $storage = sys_get_temp_dir().'/filament-data-grid-storage-'.$worker;

        File::ensureDirectoryExists($storage.'/framework/views');
        File::ensureDirectoryExists($storage.'/framework/cache');

        $app->useStoragePath($storage);
        $app['config']->set('view.compiled', $storage.'/framework/views');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentDataGridServiceProvider::class,
            WorkbenchServiceProvider::class,
            DemoPanelProvider::class,
        ];
    }
}
