<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @property-read Schema $form
 */
final class ContentWidthsDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public function mount(): void
    {
        $this->form->fill([
            'dispatch' => [
                ['order' => 'ORD-2026-0001', 'customer' => 'Hernández y Asociados', 'product' => 'Detergente líquido 5 L', 'code' => null, 'warehouse' => 'north', 'status' => 'pending'],
                ['order' => 'ORD-2026-0002', 'customer' => 'Ana Ruiz', 'product' => 'Cloro', 'code' => 'CL-0042', 'warehouse' => null, 'status' => 'ready'],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dispatch')->schema([
                    DataGrid::make('dispatch')
                        ->hiddenLabel()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->frozenColumns(1)
                        ->flexibleColumnMinWidth('10rem')
                        ->table([
                            TableColumn::make('Order'),
                            TableColumn::make('Customer'),
                            TableColumn::make('Product'),
                            TableColumn::make('Scan'),
                            TableColumn::make('Warehouse'),
                            TableColumn::make('Status'),
                        ])
                        ->schema([
                            TextEntry::make('order')->weight('medium'),
                            TextEntry::make('customer'),
                            TextEntry::make('product'),
                            TextInput::make('code')->placeholder('Scan a code')->suffixIcon(Heroicon::OutlinedQrCode),
                            Select::make('warehouse')->options(['north' => 'North', 'south' => 'South > Main loading dock'])->placeholder('Pick one'),
                            TextEntry::make('status')->badge()->icon(Heroicon::OutlinedClock),
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
