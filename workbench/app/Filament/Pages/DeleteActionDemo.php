<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @property-read Schema $form
 */
final class DeleteActionDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    #[Url]
    public bool $empty = false;

    public function mount(): void
    {
        $this->form->fill([
            'lines' => $this->empty ? [] : [['product' => 'Fraser Fir', 'note' => null]],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lines')->schema([
                    DataGrid::make('lines')
                        ->hiddenLabel()
                        ->reorderable(false)
                        ->table([
                            TableColumn::make('Product'),
                            TableColumn::make('Note'),
                        ])
                        ->schema([
                            TextInput::make('product'),
                            TextInput::make('note'),
                        ]),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }
}
