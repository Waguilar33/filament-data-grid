<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Waguilar\FilamentDataGrid\Cells\ResolveCellEditorType;
use Waguilar\FilamentDataGrid\DataGrid;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedCellComponent;

/**
 * @param  list<Action|ActionGroup>  $actions
 */
function actionsCellGrid(array $actions, bool $trailingEmptyRow = false): Closure
{
    return fn (): array => [
        DataGrid::make('lines')
            ->trailingEmptyRow($trailingEmptyRow, filledBy: 'name')
            ->table([TableColumn::make('Name'), TableColumn::make('Actions')])
            ->schema([
                TextInput::make('name'),
                Actions::make($actions)->key('lineActions'),
            ]),
    ];
}

function markAction(): Action
{
    return Action::make('mark')
        ->visible(fn (Get $get): bool => $get('name') !== 'locked')
        ->action(fn (Get $get, Set $set) => $set('name', "{$get('name')} marked"));
}

it('renders an Actions component as a read-only cell with its row actions', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'locked']], actionsCellGrid([markAction()->label('Mark')]));

    $payload = grid($component)->getGridPayload();
    $keys = rowKeys($component);

    expect(collect($payload['columns'])->firstWhere('field', 'lineActions')['editor']['type'])->toBe('entry')
        ->and($payload['rows'][0]['cells']['lineActions']['html'])->toContain("mountAction('mark'")->toContain("lines.{$keys[0]}.lineActions")
        ->and($payload['rows'][1]['cells']['lineActions']['visible'])->toBeFalse();
});

it('runs an action in an Actions cell with the row as its Get and Set', function (): void {
    $component = mountLines([['name' => 'a'], ['name' => 'b']], actionsCellGrid([markAction()]));

    $keys = rowKeys($component);

    $component->callAction(TestAction::make('mark')->schemaComponent("lines.{$keys[1]}.lineActions"));

    expect($component->get("data.lines.{$keys[0]}.name"))->toBe('a')
        ->and($component->get("data.lines.{$keys[1]}.name"))->toBe('b marked');
});

it('hides an action in an Actions cell by its row', function (): void {
    $component = mountLines([['name' => 'locked']], actionsCellGrid([Action::make('open'), markAction()]));

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['lineActions']['html'])->toContain("mountAction('open'")->not->toContain("mountAction('mark'");
});

it('runs a grouped action in an Actions cell whose sibling reads the row', function (): void {
    $component = mountLines([['name' => 'a']], actionsCellGrid([
        ActionGroup::make([
            Action::make('archive')->visible(fn (Get $get): bool => $get('name') === 'a'),
            markAction(),
        ]),
    ]));

    $key = rowKeys($component)[0];

    $component->callAction(TestAction::make('mark')->schemaComponent("lines.{$key}.lineActions"));

    expect($component->get("data.lines.{$key}.name"))->toBe('a marked');
});

it('opens a group in an Actions cell below the cell, outside the grid', function (): void {
    $component = mountLines([['name' => 'a']], actionsCellGrid([ActionGroup::make([markAction()->label('Mark')])]));

    $html = grid($component)->getGridPayload()['rows'][0]['cells']['lineActions']['html'];

    expect($html)->toContain('fi-dropdown')->toContain('placement.bottom-end.flip.teleport');
});

it('keeps a dropdown placement the group sets itself', function (): void {
    $component = mountLines([['name' => 'a']], actionsCellGrid([ActionGroup::make([markAction()->label('Mark')])->dropdownPlacement('top-start')]));

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['lineActions']['html'])->toContain('top-start');
});

it('shows no actions on the trailing blank row', function (): void {
    $component = mountLines([['name' => 'a']], actionsCellGrid([markAction()->label('Mark')], trailingEmptyRow: true));

    $payload = grid($component)->getGridPayload();

    expect($payload['rows'])->toHaveCount(2)
        ->and($payload['rows'][0]['cells']['lineActions']['visible'])->toBeTrue()
        ->and($payload['rows'][1]['cells']['lineActions']['visible'])->toBeFalse();
});

it('does not count an Actions cell as a field that fills the row', function (): void {
    $component = mountLines([['name' => 'a']], fn (): array => [
        DataGrid::make('lines')->trailingEmptyRow()->schema([TextInput::make('name'), Actions::make([markAction()])->key('lineActions')]),
    ]);

    expect(grid($component)->getTrailingEmptyRowFilledBy())->toBe(['name']);
});

it('asks for a key on an Actions cell', function (): void {
    app(ResolveCellEditorType::class)->handle(Actions::make([markAction()]));
})->throws(UnsupportedCellComponent::class, 'needs a key()');
