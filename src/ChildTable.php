<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class ChildTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public mixed $record = null;

    public function placeholder(): View
    {
        return view('filament-data-grid::child-table-placeholder');
    }

    public function render(): View
    {
        return view('filament-data-grid::child-table');
    }
}
