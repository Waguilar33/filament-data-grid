<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Waguilar\FilamentDataGrid\DataGrid;

it('puts three or more row actions in a row menu', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')
            ->extraItemActions([
                Action::make('duplicate')->label('Duplicate'),
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
        ->toContain('Row actions')
        ->not->toContain('fi-data-grid-inline-actions');

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

it('shows one or two row actions inline as link actions', function (int $extra): void {
    $actions = array_map(
        fn (int $index): Action => Action::make("extra{$index}")->label("Extra {$index}")->icon('heroicon-o-cube'),
        range(1, $extra),
    );

    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')
            ->extraItemActions($actions)
            ->deletable($extra === 1)
            ->schema([TextInput::make('name')]),
    ]);

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-data-grid-inline-actions')
        ->toContain('fi-link')
        ->toContain('Extra 1')
        ->not->toContain('fi-dropdown')
        ->not->toContain('Row actions');
})->with([1, 2]);

it('shows the delete action inline as a link when it is the only action', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('name')]),
    ]);

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-data-grid-inline-actions')
        ->toContain('fi-link')
        ->toContain("mountAction('delete'")
        ->not->toContain('fi-dropdown');
});

it('decides inline or menu per row from the actions visible for that row', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'b']], fn (): array => [
        DataGrid::make('lines')
            ->extraItemActions([
                Action::make('one')->label('One'),
                Action::make('two')->label('Two')->visible(fn (array $arguments, DataGrid $component): bool => $component->getRawItemState($arguments['item'])['name'] === 'a'),
            ])
            ->schema([TextInput::make('name')]),
    ]);

    $payload = grid($component)->getGridPayload();

    expect(rowActionsHtml($payload, 0))->toContain('fi-dropdown')
        ->and(rowActionsHtml($payload, 1))->toContain('fi-data-grid-inline-actions')
        ->and(rowActionsHtml($payload, 1))->not->toContain('Two');
});

it('replaces the row key in inline actions with the shared placeholder', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'b']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('name')]),
    ]);

    $payload = grid($component)->getGridPayload();
    $keys = rowKeys($component);

    expect($payload['rows'][0]['actions'])->toBe($payload['rows'][1]['actions'])
        ->and($payload['actionTemplates'][$payload['rows'][0]['actions']])->toContain($payload['rowKeyPlaceholder'])
        ->and(rowActionsHtml($payload, 1))->toContain($keys[1]);
});
