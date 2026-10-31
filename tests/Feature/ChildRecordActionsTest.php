<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Exceptions\DuplicateRecordActionName;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedRecordActions;
use Waguilar\FilamentDataGrid\Tests\Fixtures\PaymentsForm;

afterEach(function (): void {
    PaymentsForm::$configureUsing = null;
    PaymentsForm::$initialData = [];
});

/**
 * @param  array<string, array{method: string, amount: int}>  $payments
 * @return array<string, array{method: string, total: int, payments: array<string, array{method: string, amount: int}>}>
 */
function groupPayments(array $payments): array
{
    $groups = [];

    foreach ($payments as $key => $payment) {
        $groups[$payment['method']] ??= ['method' => $payment['method'], 'total' => 0, 'payments' => []];
        $groups[$payment['method']]['total'] += $payment['amount'];
        $groups[$payment['method']]['payments'][$key] = $payment;
    }

    return $groups;
}

function removePaymentAction(): Action
{
    return Action::make('remove')->action(function (array $record, Get $get, Set $set): void {
        $payments = $get('payments');
        unset($payments[$record['__key']]);
        $set('payments', $payments, shouldCallUpdatedHooks: true);
    });
}

function editPaymentAction(): Action
{
    return Action::make('edit')
        ->fillForm(fn (array $record): array => ['amount' => $record['amount']])
        ->schema([TextInput::make('amount')->integer()->required()->maxValue(1000)])
        ->action(fn (array $data, array $record, Set $set) => $set("payments.{$record['__key']}.amount", (int) $data['amount'], shouldCallUpdatedHooks: true));
}

/**
 * @param  Closure(DataGridEntry): DataGridEntry|null  $configure
 */
function mountPayments(?ArrayObject $hooks = null, ?Closure $configure = null): Testable
{
    PaymentsForm::$initialData = ['payments' => [
        'uuid-a' => ['method' => 'Cash', 'amount' => 10],
        'uuid-b' => ['method' => 'Cash', 'amount' => 7],
        'uuid-c' => ['method' => 'Card', 'amount' => 5],
    ]];
    PaymentsForm::$configureUsing = fn (): array => [
        Section::make('Payments')->schema([
            Hidden::make('payments')->afterStateUpdated(fn () => $hooks?->append(true)),
            ($configure ?? fn (DataGridEntry $entry): DataGridEntry => $entry)(
                DataGridEntry::make('payments_grid')
                    ->state(fn (Get $get): array => groupPayments($get('payments') ?? []))
                    ->schema([
                        TextEntry::make('method'),
                        TextEntry::make('total'),
                        RepeatableEntry::make('payments')
                            ->table([TableColumn::make('Amount')])
                            ->schema([TextEntry::make('amount')]),
                    ])
                    ->childRecordActions([editPaymentAction(), removePaymentAction()]),
            ),
        ]),
    ];

    return Livewire::test(PaymentsForm::class);
}

function jsonArgument(string $name, string $value): string
{
    return str_replace('"', '\\u0022', mb_substr(json_encode([$name => $value], JSON_THROW_ON_ERROR), 1, -1));
}

function childAction(string $name, string $row, string $child): TestAction
{
    return TestAction::make($name)->schemaComponent('payments_grid', schema: 'form')->arguments(['row' => $row, 'child' => $child]);
}

/**
 * @return array<string, mixed>
 */
function renderedPayload(Testable $component): array
{
    preg_match('/data-payload="([^"]*)"/', $component->html(), $matches);

    return json_decode(html_entity_decode($matches[1] ?? '{}'), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<string, mixed>
 */
function paymentsPayload(Testable $component): array
{
    $entry = $component->instance()->getSchema('form')?->getComponent('payments_grid');

    expect($entry)->toBeInstanceOf(DataGridEntry::class);

    return $entry->getGridPayload();
}

it('adds an actions column to the child rows and renders each child row its own actions', function (): void {
    $payload = paymentsPayload(mountPayments());
    $cash = collect($payload['rows'])->firstWhere('key', 'Cash');

    expect(array_column($payload['childColumns'], 'actions'))->toBe([true])
        ->and(end($payload['childColumns'])['headerHidden'])->toBeTrue()
        ->and($cash['children'])->toHaveCount(2)
        ->and($cash['children'][0][1]['html'])->toContain('mountAction(\'edit\'')
        ->toContain(jsonArgument('row', 'Cash'))
        ->toContain(jsonArgument('child', 'uuid-a'))
        ->and($cash['children'][1][1]['html'])->toContain(jsonArgument('child', 'uuid-b'))
        ->and($cash['actions'])->toBeNull();
});

it('removes a child row from the form state, calls the updated hooks and re-renders', function (): void {
    $hooks = new ArrayObject;
    $component = mountPayments($hooks)->callAction(childAction('remove', 'Cash', 'uuid-a'));

    expect($component->get('data.payments'))->toBe([
        'uuid-b' => ['method' => 'Cash', 'amount' => 7],
        'uuid-c' => ['method' => 'Card', 'amount' => 5],
    ])
        ->and($hooks)->toHaveCount(1)
        ->and(collect(paymentsPayload($component)['rows'])->firstWhere('key', 'Cash')['children'])->toHaveCount(1);

    $component->callAction(childAction('remove', 'Card', 'uuid-c'));

    expect(array_column(paymentsPayload($component)['rows'], 'key'))->toBe(['Cash']);
});

it('fills, validates and saves a child row through a modal form', function (): void {
    $component = mountPayments()
        ->mountAction(childAction('edit', 'Cash', 'uuid-b'))
        ->assertSchemaStateSet(['amount' => 7])
        ->fillForm(['amount' => 5000])
        ->callMountedAction()
        ->assertHasFormErrors(['amount' => 'max'])
        ->fillForm(['amount' => 9])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect($component->get('data.payments.uuid-b.amount'))->toBe(9)
        ->and($component->get('mountedActions'))->toBe([])
        ->and(strip_tags(collect(renderedPayload($component)['rows'])->firstWhere('key', 'Cash')['cells']['total']['html']))->toContain('19');
});

it('gives a child action its parent row through the entry', function (): void {
    $seen = new ArrayObject;

    mountPayments(configure: fn (DataGridEntry $entry): DataGridEntry => $entry->childRecordActions([
        Action::make('inspect')->action(function (DataGridEntry $component, array $arguments, array $record) use ($seen): void {
            $seen->exchangeArray([$component->resolveRecordActionRecord($arguments['row'])['method'], $record['amount']]);
        }),
    ]))->callAction(childAction('inspect', 'Card', 'uuid-c'));
    expect($seen->getArrayCopy())->toBe(['Card', 5]);
});

it('runs nothing when the child row is gone', function (): void {
    $component = mountPayments()->callAction(childAction('remove', 'Cash', 'uuid-a'));

    $component->call('mountAction', 'remove', ['row' => 'Cash', 'child' => 'uuid-a'], ['schemaComponent' => 'form.payments_grid'])
        ->assertSet('mountedActions', []);

    $component->call('mountAction', 'remove', ['row' => 'Card', 'child' => 'uuid-b'], ['schemaComponent' => 'form.payments_grid'])
        ->assertSet('mountedActions', []);

    expect(array_keys($component->get('data.payments')))->toBe(['uuid-b', 'uuid-c']);
});

function paymentsEntry(Closure $configure): DataGridEntry
{
    $entry = mountPayments(configure: fn (DataGridEntry $entry): DataGridEntry => $entry->childRecordActions([]))
        ->instance()->getSchema('form')?->getComponent('payments_grid');

    expect($entry)->toBeInstanceOf(DataGridEntry::class);

    return $configure($entry->childRecordActions([removePaymentAction()]));
}

it('refuses a child action named like a parent action', function (): void {
    $entry = paymentsEntry(fn (DataGridEntry $entry): DataGridEntry => $entry->recordActions([Action::make('remove')]));

    expect(fn () => $entry->getDefaultActions())->toThrow(DuplicateRecordActionName::class);
});

it('refuses child actions without nested table rows, with server-side pages or a child table', function (Closure $configure): void {
    expect(fn () => paymentsEntry($configure)->getGridPayload())->toThrow(UnsupportedRecordActions::class);
})->with([
    'no nested table' => fn (DataGridEntry $entry): DataGridEntry => $entry->schema([TextEntry::make('method')]),
    'server side' => fn (DataGridEntry $entry): DataGridEntry => $entry->serverSide(),
    'child table' => fn (DataGridEntry $entry): DataGridEntry => $entry->childTable('some-table'),
]);
