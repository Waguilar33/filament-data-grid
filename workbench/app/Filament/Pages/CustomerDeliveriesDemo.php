<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

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
use Illuminate\Support\Arr;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Support\IconHeader;
use Workbench\App\Catalog\FakeDeliveries;

final class CustomerDeliveriesDemo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.route.frozen_navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.route.frozen_navigation');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('demo::demo.route.sections.frozen'))
            ->header(IconHeader::make(Heroicon::OutlinedUsers))
            ->description(__('demo::demo.route.descriptions.frozen'))
            ->records(fn (): array => Arr::keyBy(FakeDeliveries::all(count: 8), 'delivery_number'))
            ->columns([TextColumn::make('delivery_number')])
            ->recordUrl(fn (): string => DeliveryRouteDemo::getUrl())
            ->paginated(false)
            ->content(DataGridEntry::make('customer_deliveries')
                ->hiddenLabel()
                ->frozenColumns()
                ->table([
                    TableColumn::make(__('demo::demo.route.columns.customer')),
                    TableColumn::make(__('demo::demo.route.columns.delivery')),
                    TableColumn::make(__('demo::demo.route.details.route')),
                    TableColumn::make(__('demo::demo.route.details.address')),
                    TableColumn::make(__('demo::demo.route.columns.warehouse')),
                ])
                ->schema([
                    TextEntry::make('customer')->weight('medium'),
                    TextEntry::make('delivery_number'),
                    TextEntry::make('route'),
                    TextEntry::make('address'),
                    TextEntry::make('fill_status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __("demo::demo.route.fill_statuses.{$state}"))
                        ->color(fn (string $state): string => $state === 'complete' ? 'success' : 'warning'),
                ])
                ->toTableContent());
    }
}
