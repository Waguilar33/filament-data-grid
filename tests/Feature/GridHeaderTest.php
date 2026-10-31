<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\DataGrid;

it('draws an editable grid heading as a Filament section header around the grid', function (): void {
    $html = mountLines([['name' => 'Tea']], fn (): array => [
        DataGrid::make('lines')
            ->heading('Lines')
            ->description('What leaves the store')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->compact()
            ->schema([TextInput::make('name')]),
    ])->html();

    expect($html)->toContain('fi-section fi-section-has-header fi-data-grid-section fi-compact')
        ->toContain('fi-section-header-heading')
        ->toContain('What leaves the store')
        ->toContain('fi-data-grid-has-header fi-data-grid-compact');
});

it('leaves an editable grid without a heading as it was', function (): void {
    $html = mountLines([['name' => 'Tea']], fn (): array => [
        DataGrid::make('lines')->compact()->schema([TextInput::make('name')]),
    ])->html();

    expect($html)->not->toContain('fi-data-grid-section')
        ->not->toContain('fi-data-grid-has-header');
});

it('keeps accented cell text readable in the grid payload', function (): void {
    mountLines([['name' => 'Árbol natural']], fn (): array => [
        DataGrid::make('lines')->schema([TextEntry::make('name')]),
    ])->assertSee('Árbol natural');
});
