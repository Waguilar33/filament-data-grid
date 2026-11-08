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

final class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            DataGridEntry::make('order_lines')
                ->hiddenLabel()
                ->state(fn (): Collection => $this->ownerRecord instanceof DemoOrder ? $this->ownerRecord->lines()->get() : new Collection)
                ->table([
                    TableColumn::make(__('demo::demo.order_lines.columns.product')),
                    TableColumn::make(__('demo::demo.order_lines.columns.quantity'))->alignEnd(),
                ])
                ->schema([
                    TextEntry::make('product'),
                    TextEntry::make('quantity')->numeric(),
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
