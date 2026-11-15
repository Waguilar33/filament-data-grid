<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Waguilar\FilamentDataGrid\DataGrid;

it('deletes a row by its key', function (): void {
    $component = mountLines(
        [['name' => 'a'], ['name' => 'b'], ['name' => 'c']],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')])],
    );

    [, $second] = rowKeys($component);

    $component->callAction(TestAction::make('delete')->schemaComponent('lines')->arguments(['item' => $second]));

    expect(array_column(array_values($component->get('data.lines')), 'name'))->toBe(['a', 'c']);
});

it('adds a row filled with component defaults', function (): void {
    $component = mountLines(
        [['name' => 'a', 'quantity' => 4]],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name'), TextInput::make('quantity')->default(1)])],
    );

    $component->callAction(TestAction::make('add')->schemaComponent('lines'));

    expect(array_values($component->get('data.lines')))->toEqual([
        ['name' => 'a', 'quantity' => 4],
        ['name' => null, 'quantity' => 1],
    ]);
});

it('renders a delete action per row and none on the trailing blank row', function (): void {
    $component = mountLines(
        [['name' => 'a']],
        fn (): array => [DataGrid::make('lines')->trailingEmptyRow()->schema([TextInput::make('name')])],
    );

    $payload = grid($component)->getGridPayload();

    expect(rowActionsHtml($payload, 0))->toContain("mountAction('delete'")
        ->and(rowActionsHtml($payload, 1))->toBeNull()
        ->and($payload['actionTemplates'])->toHaveCount(1);
});

it('renders the delete action as a link by default', function (): void {
    $component = mountLines(
        [['name' => 'a']],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')])],
    );

    expect(rowActionsHtml(grid($component)->getGridPayload(), 0))
        ->toContain('fi-ac-link-action')
        ->toContain('fi-link-label')
        ->not->toContain('fi-icon-btn');
});

it('renders the delete action in the style the grid configures', function (): void {
    $component = mountLines(
        [['name' => 'a']],
        fn (): array => [DataGrid::make('lines')->deleteAction(fn (Action $action): Action => $action->iconButton())->schema([TextInput::make('name')])],
    );

    expect(rowActionsHtml(grid($component)->getGridPayload(), 0))
        ->toContain('fi-icon-btn')
        ->not->toContain('fi-ac-link-action')
        ->not->toContain('fi-link-label')
        ->not->toContain('>Delete<');
});
