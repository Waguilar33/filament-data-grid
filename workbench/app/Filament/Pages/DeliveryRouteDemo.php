<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
            Section::make(__('demo::demo.route.sections.browser'))
                ->schema([
                    $this->deliveries('deliveries')
                        ->state(FakeDeliveries::all()),
                ]),
            Section::make(__('demo::demo.route.sections.server'))
                ->schema([
                    $this->deliveries('server_deliveries')
                        ->serverSide()
                        ->records(fn (?string $search, int $page, ?int $recordsPerPage): LengthAwarePaginator => $this->searchDeliveries($search, $page, $recordsPerPage)),
                ]),
        ]);
    }

    private function deliveries(string $name): DataGridEntry
    {
        return DataGridEntry::make($name)
            ->hiddenLabel()
            ->searchable(['delivery_number', 'customer'])
            ->paginated([5, 10, 25, DataGridEntry::ALL_RECORDS])
            ->defaultPaginationPageOption(10)
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
            ]);
    }

    /**
     * @return LengthAwarePaginator<int, array{delivery_number: string, customer: string, fill_status: string, lines: list<array{product: string, code: string|null, requested: int, handed_over: int}>}>
     */
    private function searchDeliveries(?string $search, int $page, ?int $recordsPerPage): LengthAwarePaginator
    {
        $deliveries = collect(FakeDeliveries::all(count: 240))
            ->when(filled($search), fn (Collection $deliveries): Collection => $deliveries->filter(
                fn (array $delivery): bool => Str::contains($delivery['delivery_number'].' '.$delivery['customer'], (string) $search, ignoreCase: true),
            ))
            ->values();

        $perPage = $recordsPerPage ?? max(1, $deliveries->count());

        return new LengthAwarePaginator($deliveries->forPage($page, $perPage)->values(), $deliveries->count(), $perPage, $page);
    }
}
