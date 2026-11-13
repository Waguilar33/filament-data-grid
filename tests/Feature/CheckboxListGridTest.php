<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Features\SupportTesting\Testable;
use Waguilar\FilamentDataGrid\DataGridCheckboxList;
use Workbench\App\Models\DemoOrder;

/**
 * @return array<string, array{name: string, ready: bool}>
 */
function pickableJobs(): array
{
    return [
        'job-1' => ['name' => 'Tea', 'ready' => true],
        'job-2' => ['name' => 'Coffee', 'ready' => false],
        'job-3' => ['name' => 'Milk', 'ready' => true],
    ];
}

function mountPicker(?Closure $configure = null): Testable
{
    return mountLines([], fn (): array => [
        ($configure ?? fn (DataGridCheckboxList $field): DataGridCheckboxList => $field)(
            DataGridCheckboxList::make('jobs')
                ->records(fn (): array => pickableJobs())
                ->checkIfRecordIsSelectableUsing(fn (array $record): bool => $record['ready'])
                ->schema([TextEntry::make('name')]),
        ),
        Text::make(fn (Get $get): string => 'Picked: '.implode(',', $get('jobs') ?? []))->key('picked'),
    ]);
}

function picker(Testable $component): DataGridCheckboxList
{
    $field = $component->instance()->form->getComponent('jobs');

    expect($field)->toBeInstanceOf(DataGridCheckboxList::class);

    return $field;
}

it('starts with no selection and keeps the selected keys as a list of strings', function (): void {
    $component = mountPicker();

    expect($component->get('data.jobs'))->toBe([]);

    $component->set('data.jobs', ['job-1', 'job-3'])->call('save')->assertHasNoErrors();

    expect(picker($component)->getState())->toBe(['job-1', 'job-3'])
        ->and($component->instance()->form->getState())->toBe(['jobs' => ['job-1', 'job-3']]);
});

it('lets other fields read the selection with $get', function (): void {
    mountPicker()->set('data.jobs', ['job-3'])->assertSee('Picked: job-3');
});

it('rejects a key that cannot be selected or is not one of the rows', function (string $key): void {
    mountPicker()->set('data.jobs', ['job-1', $key])->call('save')->assertHasErrors(['data.jobs.1' => 'in'])->assertSee('The selected jobs is invalid.');
})->with(['unselectable' => 'job-2', 'unknown' => 'job-9']);

it('sends each row, whether it can be selected, and the grid groups and columns', function (): void {
    $payload = picker(mountPicker())->getGrid()->getGridPayload();

    expect(array_column($payload['rows'], 'key'))->toBe(['job-1', 'job-2', 'job-3'])
        ->and(array_column($payload['rows'], 'selectable'))->toBe([true, false, true])
        ->and($payload['selection'])->toBe(['disabled' => false])
        ->and(array_column($payload['columns'], 'field'))->toBe(['name']);
});

it('marks the selection disabled when the field is disabled', function (): void {
    $payload = picker(mountPicker(fn (DataGridCheckboxList $field): DataGridCheckboxList => $field->disabled()))->getGrid()->getGridPayload();

    expect($payload['selection'])->toBe(['disabled' => true]);
});

it('keys Eloquent records by their model key', function (): void {
    $orders = collect(['A-1', 'B-2'])->map(fn (string $reference): DemoOrder => DemoOrder::query()->create(['reference' => $reference]));

    $component = mountLines([], fn (): array => [
        DataGridCheckboxList::make('jobs')
            ->records(fn () => DemoOrder::query()->orderBy('reference')->get())
            ->schema([TextEntry::make('reference')]),
    ]);

    $keys = array_map(strval(...), $orders->map(fn (DemoOrder $order): mixed => $order->getKey())->all());

    expect(array_column(picker($component)->getGrid()->getGridPayload()['rows'], 'key'))->toBe($keys);

    $component->set('data.jobs', [$keys[1]])->call('save')->assertHasNoErrors();
});

it('renders the field label and the grid under the field id', function (): void {
    mountPicker(fn (DataGridCheckboxList $field): DataGridCheckboxList => $field->label('Jobs to add'))
        ->assertSeeHtml('id="form.jobs"')
        ->assertSee('Jobs to add')
        ->assertSee('Coffee');
});

it('gives Eloquent rows their record in the row components, for a reason line', function (): void {
    DemoOrder::query()->create(['reference' => 'A-1']);

    $component = mountLines([], fn (): array => [
        DataGridCheckboxList::make('jobs')
            ->records(fn () => DemoOrder::query()->get())
            ->checkIfRecordIsSelectableUsing(fn (DemoOrder $record): bool => false)
            ->schema([
                TextEntry::make('reference')->belowContent(fn (DemoOrder $record): Text => Text::make("Blocked: {$record->reference}")),
            ]),
    ]);

    expect(picker($component)->getGrid()->getGridPayload()['rows'][0]['cells']['reference']['html'])->toContain('Blocked: A-1');
});
