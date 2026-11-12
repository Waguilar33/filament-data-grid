<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @property-read Schema $form
 */
final class NestedTrailingRowsDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    private const PRODUCTS = ['p1' => 'Fraser Fir', 'p2' => 'Garland', 'p3' => 'Plastic tree stand, 6 inches', 'p4' => 'Wreath'];

    public function mount(): void
    {
        $this->form->fill([
            'components' => [
                ['component_id' => 'p1', 'quantity' => 1, 'substitutes' => [['substitute_id' => 'p3']]],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Components')->schema([
                    DataGrid::make('components')
                        ->hiddenLabel()
                        ->compact()
                        ->reorderable(false)
                        ->trailingEmptyRow(filledBy: 'component_id')
                        ->table([TableColumn::make('Product'), TableColumn::make('Quantity')])
                        ->schema([
                            Select::make('component_id')->options(self::PRODUCTS)->searchable()->live(),
                            TextInput::make('quantity')->numeric()->default(1),
                            DataGrid::make('substitutes')
                                ->hiddenLabel()
                                ->compact()
                                ->trailingEmptyRow(filledBy: 'substitute_id')
                                ->table([TableColumn::make('Substitutes')])
                                ->schema([
                                    Select::make('substitute_id')->options(self::PRODUCTS)->searchable()->live(),
                                ]),
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
