<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Tests\Fixtures;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EntryPage extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public static ?Closure $configureUsing = null;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components(self::$configureUsing ? (self::$configureUsing)() : []);
    }

    public function render(): View
    {
        return view('filament-data-grid-tests::entry-page');
    }
}
