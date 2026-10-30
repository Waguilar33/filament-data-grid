<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Waguilar\FilamentDataGrid\DataGrid;
use Workbench\App\Catalog\FakeDispatch;

/**
 * @property-read Schema $form
 */
final class DispatchDemo extends Page
{
    protected static ?int $navigationSort = 4;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.dispatch.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.dispatch.navigation');
    }

    public function mount(): void
    {
        $this->form->fill(['products' => FakeDispatch::products()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('demo::demo.dispatch.section'))
                    ->description(__('demo::demo.dispatch.description'))
                    ->schema([self::productsGrid()]),
            ])
            ->statePath('data');
    }

    public static function productsGrid(): DataGrid
    {
        return DataGrid::make('products')
            ->hiddenLabel()
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->expandRowsWhen(fn (Get $get): bool => self::assigned($get) < self::integer($get('requested')))
            ->table([
                TableColumn::make(__('demo::demo.dispatch.columns.product')),
                TableColumn::make(__('demo::demo.dispatch.columns.requested'))->width('8rem')->alignEnd(),
                TableColumn::make(__('demo::demo.dispatch.columns.assigned'))->width('8rem')->alignEnd(),
                TableColumn::make(__('demo::demo.dispatch.columns.status'))->width('9rem'),
            ])
            ->schema([
                Hidden::make('id'),
                Hidden::make('has_codes'),
                TextEntry::make('name'),
                TextEntry::make('requested')->numeric(),
                TextEntry::make('assigned')
                    ->state(fn (Get $get): int => self::assigned($get))
                    ->numeric(),
                TextEntry::make('status')
                    ->badge()
                    ->state(fn (Get $get): string => self::status($get))
                    ->formatStateUsing(fn (string $state): string => __("demo::demo.dispatch.statuses.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'complete' => 'success',
                        'partial' => 'warning',
                        default => 'gray',
                    }),
                self::placesGrid(),
                self::unitsGrid(),
            ]);
    }

    private static function placesGrid(): DataGrid
    {
        return DataGrid::make('places')
            ->hiddenLabel()
            ->visible(fn (Get $get): bool => ! $get('has_codes'))
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->table([
                TableColumn::make(__('demo::demo.dispatch.columns.place')),
                TableColumn::make(__('demo::demo.dispatch.columns.available'))->width('8rem')->alignEnd(),
                TableColumn::make(__('demo::demo.dispatch.columns.quantity'))->width('8rem')->alignEnd(),
            ])
            ->schema([
                TextEntry::make('place'),
                TextEntry::make('available')->numeric(),
                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn (Get $get): int => self::integer($get('available')))
                    ->live(),
            ]);
    }

    private static function unitsGrid(): DataGrid
    {
        return DataGrid::make('units')
            ->hiddenLabel()
            ->visible(fn (Get $get): bool => (bool) $get('has_codes'))
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->table([
                TableColumn::make(__('demo::demo.dispatch.columns.place')),
                TableColumn::make(__('demo::demo.dispatch.columns.code'))->markAsRequired(),
            ])
            ->schema([
                TextEntry::make('place'),
                TextInput::make('code')->required()->maxLength(20)->live(),
            ]);
    }

    public static function assigned(Get $get): int
    {
        $field = $get('has_codes') ? 'units' : 'places';
        $rows = $get($field);

        if (! is_array($rows)) {
            return 0;
        }

        return array_sum(array_map(
            fn (mixed $row): int => match (true) {
                ! is_array($row) => 0,
                $field === 'units' => filled($row['code'] ?? null) ? 1 : 0,
                default => self::integer($row['quantity'] ?? null),
            },
            $rows,
        ));
    }

    private static function integer(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function status(Get $get): string
    {
        $assigned = self::assigned($get);

        return match (true) {
            $assigned === 0 => 'empty',
            $assigned < self::integer($get('requested')) => 'partial',
            default => 'complete',
        };
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();

        Notification::make()
            ->title(__('demo::demo.dispatch.saved'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switchLocale')
                ->label(app()->getLocale() === 'es' ? 'English' : 'Español')
                ->link()
                ->url(fn (): string => self::getUrl(['locale' => app()->getLocale() === 'es' ? 'en' : 'es'])),
        ];
    }
}
