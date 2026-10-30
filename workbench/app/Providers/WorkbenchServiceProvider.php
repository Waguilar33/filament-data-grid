<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Workbench\App\Livewire\ShiftMovementsTable;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::addLocation(__DIR__.'/../../resources/views');
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'demo');
        Livewire::component('shift-movements-table', ShiftMovementsTable::class);
    }
}
