<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Waguilar\FilamentDataGrid\Support\IconHeading;
use Workbench\App\Catalog\FakeDeliveries;

final class DeliveryTableDemo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.route.table_navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.route.table_navigation');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(IconHeading::make(__('demo::demo.route.sections.server'), Heroicon::OutlinedTruck))
            ->description(__('demo::demo.route.descriptions.server'))
            ->records(fn (?string $search, array $filters, int $page, int $recordsPerPage): LengthAwarePaginator => $this->deliveries($search, $filters, $page, $recordsPerPage))
            ->columns([TextColumn::make('delivery_number')->searchable()])
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
            ->filtersTriggerAction(fn (Action $action): Action => $action->slideOver())
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label(__('demo::demo.route.actions.edit'))
                        ->action(fn (array $record) => Notification::make()->success()->title(__('demo::demo.route.actions.edited', ['number' => self::number($record)]))->send()),
                ]),
                Action::make('deliver')
                    ->label(__('demo::demo.route.actions.deliver'))
                    ->requiresConfirmation()
                    ->action(fn (array $record) => Notification::make()->success()->title(__('demo::demo.route.actions.delivered', ['number' => self::number($record)]))->send()),
                Action::make('reschedule')
                    ->label(__('demo::demo.route.actions.reschedule'))
                    ->slideOver()
                    ->schema([DatePicker::make('date')->label(__('demo::demo.route.actions.date'))->required()])
                    ->action(fn (array $record) => Notification::make()->success()->title(__('demo::demo.route.actions.rescheduled', ['number' => self::number($record)]))->send()),
            ])
            ->recordUrl(fn (array $record): string => DeliveryRouteDemo::getUrl().'#'.self::number($record))
            ->paginated([10, 25, 50])
            ->content(DeliveryRouteDemo::rows('deliveries')->toTableContent());
    }

    /**
     * @param  array<mixed>  $filters
     * @return LengthAwarePaginator<array-key, array{delivery_number: string, customer: string, fill_status: string, kind: string, holds: list<string>, address: string, phone: string, route: string, notes: string|null, lines: list<array{product: string, code: string|null, requested: int, handed_over: int}>}>
     */
    private function deliveries(?string $search, array $filters, int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $status = data_get($filters, 'fill_status.value');
        $customers = data_get($filters, 'customer.values');
        $customers = is_array($customers) ? $customers : [];

        $deliveries = collect(FakeDeliveries::all(count: 240))
            ->when(filled($search), fn (Collection $rows): Collection => $rows->filter(
                fn (array $row): bool => Str::contains($row['delivery_number'].' '.$row['customer'], (string) $search, ignoreCase: true),
            ))
            ->when(filled($status), fn (Collection $rows): Collection => $rows->where('fill_status', $status))
            ->when($customers !== [], fn (Collection $rows): Collection => $rows->whereIn('customer', $customers))
            ->keyBy('delivery_number');

        return new LengthAwarePaginator($deliveries->forPage($page, $recordsPerPage), $deliveries->count(), $recordsPerPage, $page);
    }

    /**
     * @param  array<mixed>  $record
     */
    private static function number(array $record): string
    {
        $number = $record['delivery_number'] ?? null;

        return is_string($number) ? $number : '';
    }
}
