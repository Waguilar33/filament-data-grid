<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Filament\Pages\DeliveryTableDemo;

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
