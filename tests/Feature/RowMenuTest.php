<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Waguilar\FilamentDataGrid\DataGrid;

/**
 * @param  list<Action|ActionGroup>  $actions
 */
function rowActionsGrid(array $actions, bool $deletable = false): Closure
{
    return fn (): array => [
        DataGrid::make('lines')
            ->deletable($deletable)
            ->extraItemActions($actions)
            ->schema([TextInput::make('name')]),
    ];
}

function renameAction(): Action
{
    return Action::make('rename')->action(function (array $arguments, DataGrid $component): void {
        $rows = $component->getRawState();
        $rows[$arguments['item']]['name'] = 'renamed';
        $component->rawState($rows);
    });
}

it('renders each row action as it was passed', function (int $count): void {
    $actions = array_map(
        fn (int $index): Action => Action::make("extra{$index}")->label("Extra {$index}")->icon('heroicon-o-cube'),
        range(1, $count),
    );

    $component = mountLines([['name' => 'a']], rowActionsGrid($actions));

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-data-grid-inline-actions')
        ->toContain('fi-link')
        ->not->toContain('fi-dropdown')
        ->and(substr_count($html, 'fi-link '))->toBe($count);
})->with([1, 2, 5, 15]);

it('keeps the style an action sets itself', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([
        Action::make('open')->label('Open')->button(),
        Action::make('stock')->label('Stock')->iconButton()->icon('heroicon-o-cube'),
    ]));

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-btn')->not->toContain('fi-link');
});

it('renders an action group as a dropdown', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([
        ActionGroup::make([
            Action::make('rename')->label('Rename'),
            Action::make('archive')->label('Archive'),
        ]),
    ]));

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-dropdown')
        ->toContain("mountAction('rename'")
        ->toContain("mountAction('archive'");
});

it('mixes inline actions, an action group and delete', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([
        Action::make('stock')->label('Stock'),
        ActionGroup::make([Action::make('rename')->label('Rename')]),
    ], deletable: true));

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-dropdown')
        ->toContain("mountAction('stock'")
        ->toContain("mountAction('delete'")
        ->and(strpos($html, "mountAction('stock'"))->toBeLessThan(strpos($html, "mountAction('delete'"));
});

it('shows delete on the row as a link after the extra actions', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([Action::make('stock')->label('Stock')], deletable: true));

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-link')
        ->toContain("mountAction('delete'")
        ->not->toContain('fi-dropdown')
        ->and(strpos($html, "mountAction('stock'"))->toBeLessThan(strpos($html, "mountAction('delete'"));
});

it('shows delete alone as a link', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('name')]),
    ]);

    $html = rowActionsHtml(grid($component)->getGridPayload(), 0);

    expect($html)->toContain('fi-link')
        ->toContain("mountAction('delete'")
        ->not->toContain('fi-dropdown');
});

it('runs a row action for its row', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([renameAction()]));

    $key = rowKeys($component)[0];

    $component->callAction(TestAction::make('rename')->schemaComponent('lines')->arguments(['item' => $key]));

    expect($component->get("data.lines.{$key}.name"))->toBe('renamed');
});

it('runs an action from an action group for its row', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([ActionGroup::make([renameAction()])]));

    $key = rowKeys($component)[0];

    $component->callAction(TestAction::make('rename')->schemaComponent('lines')->arguments(['item' => $key]));

    expect($component->get("data.lines.{$key}.name"))->toBe('renamed');
});

it('hides row actions the row cannot use', function (): void {
    $component = mountLines([['name' => 'a']], rowActionsGrid([Action::make('rename')->visible(false)]));

    $payload = grid($component)->getGridPayload();

    expect($payload['hasActions'])->toBeTrue()
        ->and($payload['rows'][0]['actions'])->toBeNull();
});

it('shows each row the actions visible for that row', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'b']], rowActionsGrid([
        Action::make('one')->label('One'),
        Action::make('two')->label('Two')->visible(fn (array $arguments, DataGrid $component): bool => $component->getRawItemState($arguments['item'])['name'] === 'a'),
    ]));

    $payload = grid($component)->getGridPayload();

    expect(rowActionsHtml($payload, 0))->toContain('mountAction(\'two\'')
        ->and(rowActionsHtml($payload, 1))->toContain('mountAction(\'one\'')->not->toContain('mountAction(\'two\'');
});

it('replaces the row key with the shared placeholder', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'b']], fn (): array => [
        DataGrid::make('lines')->schema([TextInput::make('name')]),
    ]);

    $payload = grid($component)->getGridPayload();
    $keys = rowKeys($component);

    expect($payload['rows'][0]['actions'])->toBe($payload['rows'][1]['actions'])
        ->and($payload['actionTemplates'][$payload['rows'][0]['actions']])->toContain($payload['rowKeyPlaceholder'])
        ->and(rowActionsHtml($payload, 1))->toContain($keys[1]);
});
