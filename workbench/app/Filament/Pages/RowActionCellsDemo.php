<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @property-read Schema $form
 */
final class RowActionCellsDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    #[Url]
    public ?string $actionsWidth = null;

    public function mount(): void
    {
        $this->form->fill([
            'lines' => [
                ['product' => 'Fraser Fir', 'quantity' => 12, 'stock' => 4, 'scanned' => false, 'note' => null],
                ['product' => 'Garland', 'quantity' => 2, 'stock' => 10, 'scanned' => false, 'note' => null],
                ['product' => 'Wreath', 'quantity' => 8, 'stock' => 3, 'scanned' => true, 'note' => null],
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
                        ->addable(fn (): bool => $this->actionsWidth !== null)
                        ->deletable(false)
                        ->reorderable(false)
                        ->table([
                            TableColumn::make('Product'),
                            TableColumn::make('Quantity')->width('10rem')->alignEnd(),
                            TableColumn::make('Note'),
                            TableColumn::make('Actions')->hiddenHeaderLabel()->width($this->actionsWidth),
                        ])
                        ->schema([
                            Hidden::make('stock'),
                            Hidden::make('scanned'),
                            TextEntry::make('product'),
                            TextInput::make('quantity')
                                ->numeric()
                                ->disabled(fn (Get $get): bool => (bool) $get('scanned'))
                                ->suffixAction(
                                    Action::make('stockWarning')
                                        ->icon(Heroicon::OutlinedExclamationTriangle)
                                        ->color('warning')
                                        ->tooltip('Not enough stock')
                                        ->visible(fn (Get $get): bool => $get->float('quantity') > $get->float('stock'))
                                        ->modalHeading(fn (Get $get): string => "Only {$get->integer('stock')} in stock")
                                        ->modalSubmitActionLabel('Use stock')
                                        ->action(fn (Get $get, Set $set) => $set('quantity', $get('stock'))),
                                ),
                            TextEntry::make('note')->placeholder('—'),
                            Actions::make([
                                ActionGroup::make([
                                    Action::make('markGift')
                                        ->label('Mark as gift')
                                        ->visible(fn (Get $get): bool => $get('note') !== 'Gift')
                                        ->action(fn (Set $set) => $set('note', 'Gift')),
                                    Action::make('clearNote')
                                        ->label('Clear note')
                                        ->action(fn (Set $set) => $set('note', null)),
                                ]),
                            ])->key('lineActions'),
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
