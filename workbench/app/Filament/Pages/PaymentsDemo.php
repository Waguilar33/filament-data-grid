<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\DataGridEntry;

/**
 * @property-read Schema $form
 */
final class PaymentsDemo extends Page
{
    protected static ?int $navigationSort = 8;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.payments.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.payments.navigation');
    }

    public function mount(): void
    {
        $this->form->fill(['payments' => [
            'a1f0' => ['method' => 'Cash', 'amount' => 100, 'reference' => 'Till 1'],
            'b2e1' => ['method' => 'Cash', 'amount' => 50, 'reference' => 'Till 2'],
            'c3d2' => ['method' => 'Card', 'amount' => 75, 'reference' => 'VISA 4242', 'authorization' => 'A-90311'],
            'd4e5' => ['method' => 'Transfer', 'amount' => 40, 'reference' => 'TRX-118', 'bank' => 'Banco Norte'],
        ]]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('demo::demo.payments.heading'))
                    ->description(__('demo::demo.payments.description'))
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->compact()
                    ->schema([
                        Hidden::make('payments')->live(),
                        TextEntry::make('total_paid')
                            ->label(__('demo::demo.payments.total'))
                            ->state(fn (Get $get): int => array_sum(array_column(self::payments($get), 'amount'))),
                        DataGridEntry::make('payments_grid')
                            ->hiddenLabel()
                            ->state(fn (Get $get): array => self::groupByMethod(self::payments($get)))
                            ->table([
                                TableColumn::make(__('demo::demo.payments.columns.method')),
                                TableColumn::make(__('demo::demo.payments.columns.total'))->alignEnd(),
                            ])
                            ->schema([
                                TextEntry::make('method'),
                                TextEntry::make('total')->numeric(),
                                RepeatableEntry::make('payments')
                                    ->table([
                                        TableColumn::make(__('demo::demo.payments.columns.amount'))->alignEnd(),
                                        TableColumn::make(__('demo::demo.payments.columns.reference')),
                                        TableColumn::make(__('demo::demo.payments.columns.authorization')),
                                        TableColumn::make(__('demo::demo.payments.columns.bank')),
                                    ])
                                    ->schema([
                                        TextEntry::make('amount')->numeric(),
                                        TextEntry::make('reference'),
                                        TextEntry::make('authorization')->visible(fn (?string $state): bool => filled($state)),
                                        TextEntry::make('bank')->visible(fn (?string $state): bool => filled($state)),
                                    ]),
                            ])
                            ->childRecordActions([
                                Action::make('edit')
                                    ->label(__('demo::demo.payments.actions.edit'))
                                    ->slideOver()
                                    ->fillForm(fn (array $record): array => ['amount' => $record['amount'], 'reference' => $record['reference']])
                                    ->schema([
                                        TextInput::make('amount')->label(__('demo::demo.payments.columns.amount'))->integer()->minValue(1)->required(),
                                        TextInput::make('reference')->label(__('demo::demo.payments.columns.reference')),
                                    ])
                                    ->action(self::updatePayment(...)),
                                Action::make('delete')
                                    ->label(__('demo::demo.payments.actions.delete'))
                                    ->color('danger')
                                    ->action(self::deletePayment(...)),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }

    /**
     * @param  array{amount: int|string, reference: string|null}  $data
     * @param  array{__key: string}  $record
     */
    private static function updatePayment(array $data, array $record, Get $get, Set $set): void
    {
        $payments = self::payments($get);
        $payments[$record['__key']] = [...$payments[$record['__key']], 'amount' => (int) $data['amount'], 'reference' => (string) $data['reference']];

        $set('payments', $payments, shouldCallUpdatedHooks: true);
    }

    /**
     * @param  array{__key: string}  $record
     */
    private static function deletePayment(array $record, Get $get, Set $set): void
    {
        $set('payments', array_diff_key(self::payments($get), [$record['__key'] => true]), shouldCallUpdatedHooks: true);
    }

    /**
     * @return array<string, array{method: string, amount: int, reference: string}>
     */
    private static function payments(Get $get): array
    {
        /** @var array<string, array{method: string, amount: int, reference: string}> */
        return (array) $get('payments');
    }

    /**
     * @param  array<string, array{method: string, amount: int, reference: string}>  $payments
     * @return array<string, array<string, mixed>>
     */
    private static function groupByMethod(array $payments): array
    {
        $groups = [];

        foreach ($payments as $key => $payment) {
            $groups[$payment['method']] ??= ['method' => $payment['method'], 'total' => 0, 'payments' => []];
            $groups[$payment['method']]['total'] += $payment['amount'];
            $groups[$payment['method']]['payments'][$key] = $payment;
        }

        return $groups;
    }
}
