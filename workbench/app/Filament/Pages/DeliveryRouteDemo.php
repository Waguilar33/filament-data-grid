<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Workbench\App\Catalog\FakeDeliveries;

final class DeliveryRouteDemo extends Page
{
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.route.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.route.navigation');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            DataGridEntry::make('deliveries')
                ->hiddenLabel()
                ->state(FakeDeliveries::all())
                ->searchable(['delivery_number', 'customer'])
                ->paginated(10)
                ->table([
                    TableColumn::make(__('demo::demo.route.columns.delivery')),
                    TableColumn::make(__('demo::demo.route.columns.customer')),
                    TableColumn::make(__('demo::demo.route.columns.warehouse'))->width('12rem'),
                ])
                ->schema([
                    TextEntry::make('delivery_number')->weight('medium'),
                    TextEntry::make('customer'),
                    TextEntry::make('fill_status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __("demo::demo.route.fill_statuses.{$state}"))
                        ->color(fn (string $state): string => $state === 'complete' ? 'success' : 'warning'),
                    RepeatableEntry::make('lines')
                        ->table([
                            TableColumn::make(__('demo::demo.route.columns.product')),
                            TableColumn::make(__('demo::demo.route.columns.code'))->width('9rem'),
                            TableColumn::make(__('demo::demo.route.columns.requested'))->width('9rem')->alignEnd(),
                            TableColumn::make(__('demo::demo.route.columns.handed_over'))->width('11rem')->alignEnd(),
                        ])
                        ->schema([
                            TextEntry::make('product'),
                            TextEntry::make('code')->badge()->color('gray')->placeholder('—'),
                            TextEntry::make('requested')->numeric(),
                            TextEntry::make('handed_over')->numeric(),
                        ]),
                ]),
        ]);
    }
}
