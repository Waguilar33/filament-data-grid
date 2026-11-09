<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Filament\Pages\CashShiftDemo;
use Workbench\App\Filament\Pages\CustomerDeliveriesDemo;
use Workbench\App\Filament\Pages\DeliveryTableDemo;
use Workbench\App\Filament\Pages\ToggleableColumnsDemo;

/**
 * @return array<string, mixed>
 */
function tableGridPayload(Testable $component): array
{
    preg_match('/data-payload="([^"]*)"/', $component->html(), $matches);

    $payload = json_decode(html_entity_decode($matches[1] ?? ''), true);

    expect($payload)->toBeArray();

    return $payload;
}

/**
 * @param  array<string, mixed>  $payload
 * @return list<string>
 */
function tableGridKeys(array $payload): array
{
    return array_column($payload['rows'], 'key');
}

beforeEach(function (): void {
    Filament::setCurrentPanel('demo');
});

it('draws the current page of a Filament table inside the table card', function (): void {
    $component = Livewire::test(DeliveryTableDemo::class);

    expect($component->html())->toContain('fi-ta-ctn')->toContain('fi-data-grid-in-table')
        ->and(tableGridKeys(tableGridPayload($component)))->toHaveCount(10)
        ->and(tableGridKeys(tableGridPayload($component))[0])->toBe('DLV-2026-0000001');
});

it('lets Filament search, filter and page the records it draws', function (): void {
    $component = Livewire::test(DeliveryTableDemo::class);

    $component->searchTable('karen');
    expect(tableGridKeys(tableGridPayload($component)))->toHaveCount(10)
        ->each->toMatch('/^DLV-2026-\d+$/')
        ->and(collect(tableGridPayload($component)['rows'])->every(fn (array $row): bool => str_contains($row['cells']['customer']['html'], 'Karen Ortiz')))->toBeTrue();

    $component->searchTable(null)->filterTable('fill_status', 'partial');
    expect(collect(tableGridPayload($component)['rows'])->every(fn (array $row): bool => str_contains($row['cells']['fill_status']['html'], 'Partial')))->toBeTrue();

    $component->resetTableFilters()->call('gotoPage', 2);
    expect(tableGridKeys(tableGridPayload($component))[0])->toBe('DLV-2026-0000011');
});

it('leaves search, filters and pagination to the table', function (): void {
    $payload = tableGridPayload(Livewire::test(DeliveryTableDemo::class));

    expect($payload['recordsPerPage'])->toBeNull()
        ->and($payload['filters'])->toBe([])
        ->and($payload['serverSide'])->toBeFalse();
});

it('sends each record its Filament record actions and a link to the record', function (): void {
    $row = tableGridPayload(Livewire::test(DeliveryTableDemo::class))['rows'][0];

    expect($row['actions'])->toContain('fi-data-grid-record-actions')->toContain('Deliver')->toContain('Reschedule')
        ->and($row['link'])->toContain('href=')->toContain('delivery-route-demo#DLV-2026-0000001');
});

it('marks a compact grid so the table header around it is compact', function (): void {
    Filament::setCurrentPanel('demo');

    expect(Livewire::test(CashShiftDemo::class)->html())->toContain('fi-data-grid-in-table fi-data-grid-compact')
        ->and(Livewire::test(DeliveryTableDemo::class)->html())->not->toContain('fi-data-grid-compact');
});

it('freezes the leading columns of a grid drawn as table content', function (): void {
    expect(tableGridPayload(Livewire::test(CustomerDeliveriesDemo::class))['frozenColumns'])->toBe(1);
});

it('leaves out the fields whose table columns are toggled off', function (): void {
    $component = Livewire::test(ToggleableColumnsDemo::class);
    $payload = tableGridPayload($component);

    expect(array_column($payload['columns'], 'field'))->toBe(['delivery_number', 'address', 'customer'])
        ->and(array_column($payload['columns'], 'label'))->toBe([__('demo::demo.route.columns.delivery'), __('demo::demo.route.details.address'), __('demo::demo.route.columns.customer')])
        ->and($payload['rows'][0]['cells'])->not->toHaveKey('route');

    $component->call('applyTableColumnManager', toggledColumns(['delivery_number' => true, 'route' => true, 'address' => false]));

    expect(array_column(tableGridPayload($component)['columns'], 'field'))->toBe(['delivery_number', 'route', 'customer'])
        ->and(tableGridPayload($component)['rows'][0]['cells'])->toHaveKey('route')->not->toHaveKey('address');
});

it('keeps fields whose table columns cannot be toggled', function (): void {
    expect(array_column(tableGridPayload(Livewire::test(CustomerDeliveriesDemo::class))['columns'], 'field'))
        ->toBe(['customer', 'delivery_number', 'route', 'address', 'fill_status']);
});

/**
 * @param  array<string, bool>  $toggled
 * @return list<array{type: string, name: string, label: string, isHidden: bool, isToggled: bool, isToggleable: bool, isToggledHiddenByDefault: bool}>
 */
function toggledColumns(array $toggled): array
{
    return array_map(
        fn (string $name, bool $isToggled): array => ['type' => 'column', 'name' => $name, 'label' => $name, 'isHidden' => false, 'isToggled' => $isToggled, 'isToggleable' => $name !== 'delivery_number', 'isToggledHiddenByDefault' => $name === 'route'],
        array_keys($toggled),
        $toggled,
    );
}
