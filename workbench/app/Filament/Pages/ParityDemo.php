<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Support\IconHeader;

final class ParityDemo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.parity.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.parity.navigation');
    }

    /**
     * @return array<string, array{product: string, quantity: int, status: string, lines: list<array{product: string, quantity: int}>}>
     */
    public static function rows(): array
    {
        return [
            'tea' => ['product' => 'Rustic Tea 100 g', 'quantity' => 2, 'status' => 'Ready', 'lines' => [['product' => 'Leaf', 'quantity' => 1]]],
            'bread' => ['product' => 'Wild Bread 100 g', 'quantity' => 40, 'status' => 'Waiting', 'lines' => [['product' => 'Flour', 'quantity' => 3]]],
            'salsa' => ['product' => 'Classic Salsa 100 g', 'quantity' => 5, 'status' => 'Ready', 'lines' => [['product' => 'Tomato', 'quantity' => 6]]],
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
            self::grid('grid')
                ->heading(__('demo::demo.parity.grid'))
                ->description(__('demo::demo.parity.grid_description'))
                ->icon(Heroicon::OutlinedTableCells),
            self::grid('nested')
                ->heading(__('demo::demo.parity.nested'))
                ->description(__('demo::demo.parity.nested_description'))
                ->icon(Heroicon::OutlinedTableCells)
                ->schema([
                    ...self::cells(),
                    RepeatableEntry::make('lines')
                        ->table([
                            TableColumn::make(__('demo::demo.parity.product')),
                            TableColumn::make(__('demo::demo.parity.quantity'))->alignEnd(),
                        ])
                        ->schema([
                            TextEntry::make('product'),
                            TextEntry::make('quantity')->numeric(),
                        ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('demo::demo.parity.native'))
            ->description(__('demo::demo.parity.native_description'))
            ->header(IconHeader::make(Heroicon::OutlinedTableCells))
            ->records(fn (): array => self::rows())
            ->columns([
                TextColumn::make('product')->label(__('demo::demo.parity.product')),
                TextColumn::make('quantity')->label(__('demo::demo.parity.quantity'))->numeric()->alignEnd(),
                TextColumn::make('status')->label(__('demo::demo.parity.status'))->badge(),
            ])
            ->recordActions([Action::make('view')->label(__('demo::demo.parity.view'))])
            ->paginated(false);
    }

    private static function grid(string $name): DataGridEntry
    {
        return DataGridEntry::make($name)
            ->hiddenLabel()
            ->state(self::rows())
            ->table([
                TableColumn::make(__('demo::demo.parity.product')),
                TableColumn::make(__('demo::demo.parity.quantity'))->alignEnd(),
                TableColumn::make(__('demo::demo.parity.status')),
            ])
            ->schema(self::cells())
            ->recordActions([Action::make('view')->label(__('demo::demo.parity.view'))]);
    }

    /**
     * @return list<TextEntry>
     */
    private static function cells(): array
    {
        return [
            TextEntry::make('product'),
            TextEntry::make('quantity')->numeric(),
            TextEntry::make('status')->badge(),
        ];
    }
}
