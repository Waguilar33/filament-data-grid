<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Workbench\App\Models\DemoOrder;
use Workbench\App\Models\DemoOrderLine;

final class OrderLinesDemo extends Page
{
    protected static ?int $navigationSort = 5;

    public DemoOrder $order;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.order_lines.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.order_lines.navigation');
    }

    public function mount(): void
    {
        $this->order = DemoOrder::query()->firstOrCreate(['reference' => 'ORD-0001']);

        if (! $this->order->lines()->exists()) {
            foreach ([['Rustic Tea 100 g', 2], ['Wild Bread 100 g', 40], ['Classic Salsa 100 g', 5]] as [$product, $quantity]) {
                $this->order->lines()->create(['product' => $product, 'quantity' => $quantity]);
            }
        }
    }

    #[On('order-line-adjusted')]
    public function refreshLines(): void {}

    public function content(Schema $schema): Schema
    {
        return $schema
            ->record($this->order)
            ->components([
                Section::make(__('demo::demo.order_lines.order'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description(__('demo::demo.order_lines.order_description'))
                    ->compact()
                    ->schema([TextEntry::make('reference')->icon(Heroicon::OutlinedHashtag)]),
                DataGridEntry::make('lines')
                    ->hiddenLabel()
                    ->heading(__('demo::demo.order_lines.section'))
                    ->description(__('demo::demo.order_lines.description'))
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->compact()
                    ->state(fn (DemoOrder $record): Collection => $record->lines()->reorder()->latest('id')->get())
                    ->table([
                        TableColumn::make(__('demo::demo.order_lines.columns.product')),
                        TableColumn::make(__('demo::demo.order_lines.columns.quantity'))->alignEnd(),
                    ])
                    ->schema([
                        TextEntry::make('product')->icon(Heroicon::OutlinedCube),
                        TextEntry::make('quantity')->numeric(),
                    ])
                    ->recordActions([
                        self::adjustAction(),
                        ActionGroup::make([
                            Action::make('remove')
                                ->label(__('demo::demo.order_lines.actions.remove'))
                                ->icon(Heroicon::OutlinedTrash)
                                ->color('danger')
                                ->requiresConfirmation()
                                ->action(fn (DemoOrderLine $record): ?bool => $record->delete()),
                        ]),
                    ]),
            ]);
    }

    private static function adjustAction(): Action
    {
        return Action::make('adjust')
            ->label(__('demo::demo.order_lines.actions.adjust'))
            ->visible(fn (DemoOrderLine $record): bool => (float) $record->quantity < 10)
            ->modalWidth(Width::Small)
            ->fillForm(fn (DemoOrderLine $record): array => ['quantity' => (float) $record->quantity])
            ->schema(fn (DemoOrderLine $record): array => [
                TextInput::make('quantity')
                    ->label(__('demo::demo.order_lines.columns.quantity'))
                    ->helperText($record->product)
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                FileUpload::make('receipt')
                    ->label(__('demo::demo.order_lines.fields.receipt'))
                    ->disk('local')
                    ->directory('receipts'),
            ])
            ->action(function (array $data, DemoOrderLine $record, Component $livewire): void {
                $record->update(['quantity' => $data['quantity']]);

                Notification::make()->success()->title(__('demo::demo.order_lines.actions.adjusted', ['product' => $record->product]))->send();

                $livewire->dispatch('order-line-adjusted');
            });
    }
}
