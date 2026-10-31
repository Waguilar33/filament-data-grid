<?php

declare(strict_types=1);

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\DataGrid;

it('locks a read-only cell like a disabled one', function (): void {
    $component = mountLines([['name' => 'A', 'due' => '2026-10-31']], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('name')->readOnly(),
            DatePicker::make('due')->readOnly(),
        ]),
    ]);

    $cells = grid($component)->getGridPayload()['rows'][0]['cells'];

    expect($cells['name']['disabled'])->toBeTrue()
        ->and($cells['due']['disabled'])->toBeTrue();
});

it('sends a field’s suffix icon once per grid and refers to it from each cell', function (): void {
    $component = mountLines([['code' => 'A'], ['code' => 'B']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('code')->suffixIcon(Heroicon::OutlinedQrCode)]),
    ]);

    $payload = grid($component)->getGridPayload();
    $key = $payload['rows'][0]['cells']['code']['icon'];

    expect($payload['cellIcons'])->toHaveCount(1)
        ->and($payload['cellIcons'][$key])->toContain('<svg')
        ->and($payload['rows'][1]['cells']['code']['icon'])->toBe($key);
});

it('evaluates the suffix icon per row and leaves it off locked cells', function (): void {
    $component = mountLines([['code' => 'A', 'locked' => false], ['code' => 'B', 'locked' => true]], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('code')
                ->suffixIcon(fn (Get $get): ?Heroicon => $get('code') === 'A' ? Heroicon::OutlinedQrCode : null)
                ->disabled(fn (Get $get): bool => (bool) $get('locked')),
            TextInput::make('other')->suffixIcon(Heroicon::OutlinedQrCode)->disabled(fn (Get $get): bool => (bool) $get('locked')),
        ]),
    ]);

    $rows = grid($component)->getGridPayload()['rows'];

    expect($rows[0]['cells']['code'])->toHaveKey('icon')
        ->and($rows[1]['cells']['code'])->not->toHaveKey('icon')
        ->and($rows[1]['cells']['other'])->not->toHaveKey('icon');
});

it('leaves editable columns unhighlighted by default', function (): void {
    $component = mountLines([['name' => 'A']], fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')])]);

    expect(grid($component)->getGridPayload()['highlightEditableColumns'])->toBeFalse();
});

it('highlights every editable column or the listed ones', function (bool|array $columns, bool|array $expected): void {
    $component = mountLines([['name' => 'A']], fn (): array => [
        DataGrid::make('lines')->highlightEditableColumns(fn (): bool|array => $columns)->schema([TextInput::make('name')]),
    ]);

    expect(grid($component)->getGridPayload()['highlightEditableColumns'])->toBe($expected);
})->with([
    'all' => [true, true],
    'listed' => [['name', 7], ['name']],
]);
