<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Waguilar33\FilamentDataGrid\DataGrid;

it('edits a field that has no inline editor in a modal for its row', function (): void {
    $component = mountLines([['name' => 'a', 'color' => '#000000'], ['name' => 'b', 'color' => null]], fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('name'),
            ColorPicker::make('color')->afterStateUpdated(fn ($set) => $set('name', 'recolored')),
        ]),
    ]);

    [, $second] = rowKeys($component);

    expect(grid($component)->getGridPayload()['rows'][0]['cells']['color'])->toMatchArray(['label' => '#000000']);

    $component->callAction(
        TestAction::make('editCell')->schemaComponent('lines')->arguments(['item' => $second, 'field' => 'color']),
        ['color' => '#ff0000'],
    );

    expect($component->get("data.lines.{$second}"))->toMatchArray(['color' => '#ff0000', 'name' => 'recolored']);
});
