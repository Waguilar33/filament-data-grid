<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Waguilar\FilamentDataGrid\DataGrid;
use Workbench\App\Models\DemoOrder;

final class OrderForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public DemoOrder $order;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(DemoOrder $order): void
    {
        $this->order = $order;
        $this->form->fill($order->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DataGrid::make('lines')
                    ->relationship()
                    ->orderColumn('sort')
                    ->schema([
                        TextInput::make('product')->required(),
                        TextInput::make('quantity')->numeric()->required(),
                    ]),
            ])
            ->statePath('data')
            ->model($this->order);
    }

    public function save(): void
    {
        $this->form->getState();
        $this->form->saveRelationships();
    }

    public function render(): View
    {
        return view('filament-data-grid-tests::lines-form');
    }
}
