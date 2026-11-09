<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Workbench\App\Catalog\FakeDeliveries;

final class ToggleableColumnsDemo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('demo::demo.route.sections.frozen'))
            ->records(fn (): array => Arr::keyBy(FakeDeliveries::all(count: 5), 'delivery_number'))
            ->columns([
                TextColumn::make('delivery_number')->label(__('demo::demo.route.columns.delivery')),
                TextColumn::make('route')->label(__('demo::demo.route.details.route'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')->label(__('demo::demo.route.details.address'))->toggleable(),
            ])
            ->paginated(false)
            ->content(DataGridEntry::make('toggleable_deliveries')
                ->hiddenLabel()
                ->frozenColumns()
                ->table([
                    TableColumn::make(__('demo::demo.route.columns.delivery')),
                    TableColumn::make(__('demo::demo.route.details.route')),
                    TableColumn::make(__('demo::demo.route.details.address')),
                    TableColumn::make(__('demo::demo.route.columns.customer')),
                ])
                ->schema([
                    TextEntry::make('delivery_number')->weight('medium'),
                    TextEntry::make('route'),
                    TextEntry::make('address'),
                    TextEntry::make('customer'),
                ])
                ->toTableContent());
    }
}
