<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Waguilar33\FilamentDataGrid\DataGrid;

it('puts extra item actions and delete in a row menu', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')
            ->extraItemActions([
                Action::make('rename')
                    ->label('Rename')
                    ->action(function (array $arguments, DataGrid $component): void {
                        $rows = $component->getRawState();
                        $rows[$arguments['item']]['name'] = 'renamed';
                        $component->rawState($rows);
                    }),
            ])
            ->schema([TextInput::make('name')]),
    ]);

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-dropdown')
        ->toContain("mountAction('rename'")
        ->toContain("mountAction('delete'")
        ->toContain('Row actions');

    $key = rowKeys($component)[0];

    $component->callAction(TestAction::make('rename')->schemaComponent('lines')->arguments(['item' => $key]));

    expect($component->get("data.lines.{$key}.name"))->toBe('renamed');
});

it('hides row actions the row cannot use', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')
            ->deletable(false)
            ->extraItemActions([Action::make('rename')->visible(false)])
            ->schema([TextInput::make('name')]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect($payload['hasActions'])->toBeTrue()
        ->and($payload['rows'][0]['actions'])->toBeNull();
});
