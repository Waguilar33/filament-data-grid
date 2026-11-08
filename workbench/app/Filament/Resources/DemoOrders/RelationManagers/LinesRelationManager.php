<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\DemoOrders\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Workbench\App\Models\DemoOrder;
use Workbench\App\Models\DemoOrderLine;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected bool $hasLoadingSkeleton = false;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            DataGridEntry::make('order_lines')
                ->hiddenLabel()
                ->state(fn (): Collection => $this->ownerRecord instanceof DemoOrder ? $this->ownerRecord->lines()->get() : new Collection)
                ->searchable(['product'])
                ->paginated([10, 25])
                ->loadingSkeleton($this->hasLoadingSkeleton)
                ->recordUrl(fn (DemoOrderLine $record): string => "#line-{$record->id}")
                ->table([
                    TableColumn::make('Number'),
                    TableColumn::make(__('demo::demo.order_lines.columns.product')),
                    TableColumn::make(__('demo::demo.order_lines.columns.quantity'))->alignEnd(),
                    TableColumn::make('Sort')->alignEnd(),
                    TableColumn::make('Created'),
                ])
                ->schema([
                    TextEntry::make('number')->state(fn (DemoOrderLine $record): string => sprintf('PRD-2026-%07d-01', $record->id)),
                    TextEntry::make('product'),
                    TextEntry::make('quantity')->numeric(),
                    TextEntry::make('sort'),
                    TextEntry::make('created_at')->dateTime(),
                ])
                ->recordActions([
                    Action::make('setQuantity')
                        ->label(__('demo::demo.order_lines.actions.adjust'))
                        ->fillForm(fn (DemoOrderLine $record): array => ['quantity' => (float) $record->quantity])
                        ->schema([
                            TextInput::make('quantity')
                                ->label(__('demo::demo.order_lines.columns.quantity'))
                                ->numeric()
                                ->required(),
                        ])
                        ->action(fn (array $data, DemoOrderLine $record): bool => $record->update(['quantity' => $data['quantity']])),
                ]),
        ]);
    }
}
