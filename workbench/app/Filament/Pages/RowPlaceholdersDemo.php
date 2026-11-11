<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @property-read Schema $form
 */
final class RowPlaceholdersDemo extends Page
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
            'lines' => [
                ['product' => null, 'store_item' => 'Corona · COR-1', 'locked' => true],
                ['product' => null, 'store_item' => 'Wreath · WR-7', 'locked' => false],
                ['product' => null, 'store_item' => null, 'locked' => false],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lines')->schema([
                    DataGrid::make('lines')
                        ->hiddenLabel()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->table([TableColumn::make('Product')])
                        ->schema([
                            Hidden::make('store_item'),
                            Hidden::make('locked'),
                            Select::make('product')
                                ->options(['p1' => 'Fraser Fir', 'p2' => 'Garland'])
                                ->placeholder(fn (Get $get): ?string => $get->string('store_item') ?: null)
                                ->disabled(fn (Get $get): bool => $get->boolean('locked')),
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
