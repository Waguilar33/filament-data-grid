<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
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
            $this->deliveries('deliveries')
                ->heading(__('demo::demo.route.sections.browser'))
                ->description(__('demo::demo.route.descriptions.browser'))
                ->icon(Heroicon::OutlinedTruck)
                ->state(FakeDeliveries::all()),
            $this->deliveries('server_deliveries')
                ->heading(__('demo::demo.route.sections.server'))
                ->description(__('demo::demo.route.descriptions.server'))
                ->icon(Heroicon::OutlinedServerStack)
                ->serverSide()
                ->records(fn (?string $search, int $page, ?int $recordsPerPage, array $filters): LengthAwarePaginator => $this->searchDeliveries($search, $page, $recordsPerPage, $filters)),
        ]);
    }

    private function deliveries(string $name): DataGridEntry
    {
        return DataGridEntry::make($name)
            ->hiddenLabel()
            ->searchable(['delivery_number', 'customer'])
            ->filters([
                SelectFilter::make('fill_status')
                    ->label(__('demo::demo.route.columns.warehouse'))
                    ->options([
                        'complete' => __('demo::demo.route.fill_statuses.complete'),
                        'partial' => __('demo::demo.route.fill_statuses.partial'),
                    ]),
                SelectFilter::make('customer')
                    ->label(__('demo::demo.route.columns.customer'))
                    ->multiple()
                    ->options(array_combine(FakeDeliveries::customers(), FakeDeliveries::customers())),
            ])
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
     * @param  array<mixed>  $filters
     * @return LengthAwarePaginator<int, array{delivery_number: string, customer: string, fill_status: string, lines: list<array{product: string, code: string|null, requested: int, handed_over: int}>}>
     */
    private function searchDeliveries(?string $search, int $page, ?int $recordsPerPage, array $filters): LengthAwarePaginator
    {
        $status = data_get($filters, 'fill_status.value');
        $customers = data_get($filters, 'customer.values');
        $customers = is_array($customers) ? $customers : [];

        $deliveries = collect(FakeDeliveries::all(count: 240))
            ->when(filled($search), fn (Collection $deliveries): Collection => $deliveries->filter(
                fn (array $delivery): bool => Str::contains($delivery['delivery_number'].' '.$delivery['customer'], (string) $search, ignoreCase: true),
            ))
            ->when(filled($status), fn (Collection $deliveries): Collection => $deliveries->where('fill_status', $status))
            ->when($customers !== [], fn (Collection $deliveries): Collection => $deliveries->whereIn('customer', $customers))
            ->values();

        $perPage = $recordsPerPage ?? max(1, $deliveries->count());

        return new LengthAwarePaginator($deliveries->forPage($page, $perPage)->values(), $deliveries->count(), $perPage, $page);
    }
}
