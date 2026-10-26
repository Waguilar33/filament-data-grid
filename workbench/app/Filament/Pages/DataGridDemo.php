<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Waguilar33\FilamentDataGrid\DataGrid;
use Workbench\App\Catalog\FakeProducts;

/**
 * @property-read Schema $form
 */
final class DataGridDemo extends Page
{
    protected string $view = 'demo.data-grid';

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.navigation');
    }

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'lines' => [
                ['product_id' => 12, 'quantity' => 2, 'unit' => 'box', 'is_gift' => false, 'note' => 'seeded'],
                ['product_id' => 77, 'quantity' => 1.5, 'unit' => 'kg', 'is_gift' => true, 'note' => null],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([self::linesGrid()]),
            ])
            ->statePath('data');
    }

    public static function linesGrid(): DataGrid
    {
        return DataGrid::make('lines')
            ->hiddenLabel()
            ->trailingEmptyRow(filledBy: 'product_id')
            ->table([
                TableColumn::make(__('demo::demo.columns.product'))->markAsRequired(),
                TableColumn::make(__('demo::demo.columns.quantity'))->width('8rem')->alignEnd(),
                TableColumn::make(__('demo::demo.columns.unit'))->width('9rem'),
                TableColumn::make(__('demo::demo.columns.gift'))->width('5rem')->alignCenter(),
                TableColumn::make(__('demo::demo.columns.total'))->width('8rem')->alignEnd(),
            ])
            ->schema([
                Select::make('product_id')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => FakeProducts::search($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => FakeProducts::find($value)['name'] ?? null)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $set('quantity', filled($state) ? 1 : null);
                    }),
                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(fn (Get $get): float => $get('unit') === 'box' ? 1.0 : 0.001)
                    ->maxValue(999)
                    ->step(fn (Get $get): int|string => $get('unit') === 'box' ? 1 : 'any')
                    ->required(fn (Get $get): bool => filled($get('product_id')))
                    ->live(),
                Select::make('unit')
                    ->options([
                        'box' => __('demo::demo.units.box'),
                        'kg' => __('demo::demo.units.kg'),
                        'unit' => __('demo::demo.units.unit'),
                    ])
                    ->default('unit')
                    ->live(),
                Toggle::make('is_gift')->live(),
                TextEntry::make('total')
                    ->state(fn (Get $get): ?float => self::lineTotal($get))
                    ->money('GTQ')
                    ->placeholder('—'),
                Hidden::make('note'),
            ]);
    }

    public static function lineTotal(Get $get): ?float
    {
        $product = FakeProducts::find($get('product_id'));

        if ($product === null) {
            return null;
        }

        if ($get('is_gift')) {
            return 0.0;
        }

        $quantity = $get('quantity');

        return is_numeric($quantity) ? round($product['price'] * (float) $quantity, 2) : null;
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->notifySaved($state['lines'] ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editInSlideOver')
                ->label(__('demo::demo.actions.edit_in_slide_over'))
                ->slideOver()
                ->modalWidth('5xl')
                ->fillForm(fn (): array => ['lines' => [['product_id' => 5, 'quantity' => 3, 'unit' => 'kg']]])
                ->schema([self::linesGrid()])
                ->action(fn (array $data) => $this->notifySaved($data['lines'] ?? [])),
        ];
    }

    private function notifySaved(mixed $lines): void
    {
        Notification::make()
            ->title(trans_choice('demo::demo.saved_lines', is_array($lines) ? count($lines) : 0))
            ->success()
            ->send();
    }
}
