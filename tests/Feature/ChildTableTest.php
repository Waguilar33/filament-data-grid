<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedChildTable;
use Workbench\App\Filament\Pages\CashShiftDemo;
use Workbench\App\Livewire\ShiftMovementsTable;

beforeEach(function (): void {
    Filament::setCurrentPanel('demo');
});

it('mounts one lazy child table per parent row beside the grid', function (): void {
    $html = Livewire::test(CashShiftDemo::class)->html();

    expect($html)->toContain('&quot;childTable&quot;:true')
        ->and(substr_count($html, 'class="fi-data-grid-child-table-host"'))->toBe(8)
        ->and($html)->toContain('data-row-key="cash"')
        ->toContain('data-row-key="voucher"')
        ->toContain('fi-data-grid-child-table-lazy')
        ->toContain('x-intersect');
});

it('gives each parent its own child table component', function (): void {
    $html = Livewire::test(CashShiftDemo::class)->html();

    preg_match_all('/wire:key="([^"]*\.child-table\.[^"]*)"/', $html, $keys);

    expect($keys[1])->toHaveCount(8)
        ->and(array_unique($keys[1]))->toHaveCount(8);
});

it('scopes the child table to the parent row', function (): void {
    Livewire::test(ShiftMovementsTable::class, ['method' => 'card'])
        ->assertCanSeeTableRecords(['MOV-0101', 'MOV-0110'])
        ->assertTableColumnVisible('details')
        ->assertCountTableRecords(27);

    Livewire::test(ShiftMovementsTable::class, ['method' => 'cash'])
        ->assertTableColumnHidden('details')
        ->assertCountTableRecords(34)
        ->searchTable('R-1034')
        ->assertCountTableRecords(1);
});

it('refuses to combine a child table with server-side pages', function (): void {
    $entry = DataGridEntry::make('methods')
        ->serverSide()
        ->records(fn (): array => [])
        ->childTable(ShiftMovementsTable::class)
        ->schema([TextEntry::make('key')]);

    expect(fn () => $entry->getGridPayload())->toThrow(UnsupportedChildTable::class)
        ->and(fn () => $entry->serverSide(false)->getGridPayload())->not->toThrow(UnsupportedChildTable::class);
});
