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
use Illuminate\Support\Str;
use Waguilar33\FilamentDataGrid\DataGrid;
use Workbench\App\Catalog\FakeProducts;
use Workbench\App\Catalog\FakeWarehouses;

/**
 * @property-read Schema $form
 */
final class DataGridDemo extends Page
{
    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.navigation');
    }

    public function mount(): void
    {
        $this->form->fill([
            'lines' => [
                ['product_id' => 12, 'quantity' => 2, 'unit' => 'box', 'warehouse' => 'z10-b6', 'is_gift' => false, 'comment' => null, 'note' => 'seeded'],
                ['product_id' => 77, 'quantity' => 1.5, 'unit' => 'kg', 'warehouse' => null, 'is_gift' => true, 'comment' => 'Fragile', 'note' => null],
            ],
            'adjustments' => [
                ['reason' => 'Damaged box', 'amount' => 12.5, 'approved' => false],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('demo::demo.sections.lines'))->schema([self::linesGrid()]),
                Section::make(__('demo::demo.sections.adjustments'))->schema([self::adjustmentsGrid()]),
            ])
            ->statePath('data');
    }

    public static function linesGrid(): DataGrid
    {
        return DataGrid::make('lines')
            ->hiddenLabel()
            ->frozenColumns()
            ->trailingEmptyRow(filledBy: 'product_id')
            ->table([
                TableColumn::make(__('demo::demo.columns.product'))->markAsRequired(),
                TableColumn::make(__('demo::demo.columns.quantity'))->width('8rem')->alignEnd(),
                TableColumn::make(__('demo::demo.columns.unit'))->width('9rem'),
                TableColumn::make(__('demo::demo.columns.warehouse'))->width('14rem'),
                TableColumn::make(__('demo::demo.columns.gift'))->width('5rem')->alignCenter(),
                TableColumn::make(__('demo::demo.columns.comment'))->width('9rem'),
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
                Select::make('warehouse')
                    ->options(FakeWarehouses::options())
                    ->searchable()
                    ->disableOptionWhen(fn (string $value): bool => FakeWarehouses::isEmpty($value)),
                Toggle::make('is_gift')->live(),
                TextInput::make('comment')
                    ->maxLength(40)
                    ->disabled(fn (Get $get): bool => blank($get('product_id'))),
                TextEntry::make('total')
                    ->state(fn (Get $get): ?float => self::lineTotal($get))
                    ->money('GTQ')
                    ->placeholder('—'),
                Hidden::make('note'),
            ]);
    }

    public static function adjustmentsGrid(): DataGrid
    {
        return DataGrid::make('adjustments')
            ->hiddenLabel()
            ->defaultItems(0)
            ->addActionLabel(__('demo::demo.actions.add_adjustment'))
            ->table([
                TableColumn::make(__('demo::demo.columns.reason'))->markAsRequired(),
                TableColumn::make(__('demo::demo.columns.amount'))->width('10rem')->alignEnd(),
                TableColumn::make(__('demo::demo.columns.approved'))->width('7rem')->alignCenter(),
            ])
            ->schema([
                TextInput::make('reason')->required(),
                TextInput::make('amount')->numeric()->minValue(0)->step(0.01)->required(),
                Toggle::make('approved'),
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
        $this->savedState = $this->form->getState();

        $this->notifySaved($this->savedState['lines'] ?? []);
    }

    public function addSampleLines(): void
    {
        $grid = $this->form->getComponent('lines');

        if (! $grid instanceof DataGrid) {
            return;
        }

        $rows = $grid->getRawState();
        $rows = is_array($rows) ? $rows : [];
        $trailingKey = array_key_last($rows);

        if ($trailingKey !== null && $grid->isTrailingBlankRow($trailingKey)) {
            unset($rows[$trailingKey]);
        }

        foreach ([101, 202] as $productId) {
            $rows[(string) Str::uuid()] = ['product_id' => (string) $productId, 'quantity' => 1, 'unit' => 'unit', 'is_gift' => false];
        }

        $grid->rawState($rows);
        $grid->ensureTrailingEmptyRow();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSampleLines')
                ->label(__('demo::demo.actions.add_sample_lines'))
                ->color('gray')
                ->action(fn () => $this->addSampleLines()),
            Action::make('editInSlideOver')
                ->label(__('demo::demo.actions.edit_in_slide_over'))
                ->slideOver()
                ->modalWidth('5xl')
                ->fillForm(fn (): array => ['lines' => [['product_id' => 5, 'quantity' => 3, 'unit' => 'kg']]])
                ->schema([self::linesGrid()])
                ->action(fn (array $data) => $this->notifySaved($data['lines'] ?? [])),
            Action::make('switchLocale')
                ->label(app()->getLocale() === 'es' ? 'English' : 'Español')
                ->link()
                ->url(fn (): string => self::getUrl(['locale' => app()->getLocale() === 'es' ? 'en' : 'es'])),
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
