<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Tests\Fixtures;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class LinesForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array<int, array<string, mixed>> */
    public static array $initialLines = [];

    public static ?Closure $configureUsing = null;

    public function mount(): void
    {
        $this->form->fill(['lines' => self::$initialLines]);
    }

    public function form(Schema $schema): Schema
    {
        $components = self::$configureUsing ? (self::$configureUsing)() : [];

        return $schema->components($components)->statePath('data');
    }

    public function save(): void
    {
        $this->form->getState();
    }

    public function render(): View
    {
        return view('filament-data-grid-tests::lines-form');
    }
}
