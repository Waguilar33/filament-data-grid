<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Waguilar\FilamentDataGrid\ChildTable;
use Workbench\App\Catalog\FakeCashShift;

final class ShiftMovementsTable extends ChildTable
{
    public string $method = 'cash';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->movements($search, $filters, $page, $recordsPerPage))
            ->columns([
                TextColumn::make('time')->label(__('demo::demo.shift.columns.time')),
                TextColumn::make('type')
                    ->label(__('demo::demo.shift.columns.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("demo::demo.shift.types.{$state}"))
                    ->color(fn (string $state): string => in_array($state, ['refund', 'cash_out'], true) ? 'danger' : 'success'),
                TextColumn::make('reference')->label(__('demo::demo.shift.columns.reference'))->searchable(),
                TextColumn::make('details')
                    ->label(__('demo::demo.shift.columns.details'))
                    ->visible(fn (): bool => $this->method !== 'cash'),
                TextColumn::make('amount')->label(__('demo::demo.shift.columns.amount'))->money('USD')->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('demo::demo.shift.columns.type'))
                    ->options(fn (): array => $this->typeOptions()),
            ])
            ->recordActions([
                Action::make('receipt')
                    ->label(__('demo::demo.shift.actions.receipt'))
                    ->link()
                    ->action(fn (array $record) => Notification::make()->success()->title(__('demo::demo.shift.actions.receipt_sent', ['reference' => is_string($record['reference'] ?? null) ? $record['reference'] : '']))->send()),
            ])
            ->toolbarActions([
                BulkAction::make('reconcile')
                    ->label(__('demo::demo.shift.actions.reconcile'))
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => Notification::make()->success()->title(trans_choice('demo::demo.shift.actions.reconciled', $records->count(), ['count' => $records->count()]))->send()),
            ])
            ->paginated([10, 25, 50, 'all']);
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $options = [];

        foreach (FakeCashShift::movements($this->method) as $movement) {
            $options[$movement['type']] = __("demo::demo.shift.types.{$movement['type']}");
        }

        return $options;
    }

    /**
     * @param  array<mixed>  $filters
     * @return LengthAwarePaginator<array-key, array{id: string, time: string, type: string, reference: string, details: string|null, amount: float}>
     */
    private function movements(?string $search, array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator
    {
        $type = data_get($filters, 'type.value');

        $movements = collect(FakeCashShift::movements($this->method))
            ->when(filled($search), fn (Collection $rows): Collection => $rows->filter(
                fn (array $row): bool => Str::contains($row['reference'], (string) $search, ignoreCase: true),
            ))
            ->when(filled($type), fn (Collection $rows): Collection => $rows->where('type', $type));

        $perPage = is_int($recordsPerPage) ? $recordsPerPage : max(1, $movements->count());

        return new LengthAwarePaginator($movements->forPage($page, $perPage), $movements->count(), $perPage, $page);
    }
}
