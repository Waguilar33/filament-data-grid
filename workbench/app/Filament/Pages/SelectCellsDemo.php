<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @property-read Schema $form
 */
final class SelectCellsDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    #[Url]
    public bool $dense = false;

    public function mount(): void
    {
        $this->form->fill([
            'lines' => [
                ['product' => null, 'quantity' => 1, 'note' => null],
                ['product' => null, 'quantity' => 1, 'note' => null],
                ['product' => null, 'quantity' => 1, 'note' => null],
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function products(): array
    {
        return collect(range(1, 50))->mapWithKeys(fn (int $number): array => ["p{$number}" => "Product {$number}"])->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lines')->schema([
                    DataGrid::make('lines')
                        ->hiddenLabel()
                        ->reorderable(false)
                        ->dense($this->dense)
                        ->table([
                            TableColumn::make('Product'),
                            TableColumn::make('Quantity')->width('8rem')->alignEnd(),
                            TableColumn::make('Note')->width('12rem'),
                        ])
                        ->schema([
                            Select::make('product')
                                ->options(self::products())
                                ->searchable()
                                ->live()
                                ->disableOptionWhen(fn (string $value, Get $get): bool => $value !== $get('product') && in_array($value, array_column($get->array('../../lines'), 'product'), true))
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    usleep(2_000_000);
                                    $set('note', filled($state) ? "Picked {$state}" : null);
                                }),
                            TextInput::make('quantity')->numeric(),
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
