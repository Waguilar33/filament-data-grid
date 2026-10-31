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

final class PaymentsForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public static ?Closure $configureUsing = null;

    /** @var array<string, mixed> */
    public static array $initialData = [];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(self::$initialData);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(self::$configureUsing ? (self::$configureUsing)() : [])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('filament-data-grid-tests::lines-form');
    }
}
