<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Hidden;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Tests\Fixtures\PaymentsForm;

afterEach(function (): void {
    PaymentsForm::$configureUsing = null;
    PaymentsForm::$initialData = [];
});

it('runs a parent record action inside a form and re-renders from the new form state', function (): void {
    $hooks = new ArrayObject;
    PaymentsForm::$initialData = ['payments' => [
        'a' => ['method' => 'Cash', 'amount' => 10],
        'b' => ['method' => 'Card', 'amount' => 5],
    ]];
    PaymentsForm::$configureUsing = fn (): array => [
        Section::make('Payments')->schema([
            Hidden::make('payments')->afterStateUpdated(fn () => $hooks->append(true)),
            DataGridEntry::make('payments_grid')
                ->state(fn (Get $get): array => $get('payments') ?? [])
                ->schema([TextEntry::make('method'), TextEntry::make('amount')])
                ->recordActions([
                    Action::make('remove')->action(function (array $record, Get $get, Set $set): void {
                        $payments = $get('payments');
                        unset($payments[$record['__key']]);
                        $set('payments', $payments, shouldCallUpdatedHooks: true);
                    }),
                ]),
        ]),
    ];

    $component = Livewire::test(PaymentsForm::class)->assertSee('Cash')->assertSee('Card');

    $component->callAction(TestAction::make('remove')->schemaComponent('payments_grid', schema: 'form')->arguments(['row' => 'a']));

    expect($component->get('data.payments'))->toBe(['b' => ['method' => 'Card', 'amount' => 5]])
        ->and($hooks)->toHaveCount(1);

    $component->assertDontSee('Cash')->assertSee('Card');
});
