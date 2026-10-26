<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Waguilar\FilamentDataGrid\DataGrid;

it('keys every row by a UUID after filling', function (): void {
    $component = mountLines(
        [['name' => 'a'], ['name' => 'b']],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')])],
    );

    $keys = rowKeys($component);

    expect($keys)->toHaveCount(2)
        ->and(array_filter($keys, Str::isUuid(...)))->toHaveCount(2);
});

it('keeps row keys stable across requests', function (): void {
    $component = mountLines(
        [['name' => 'a'], ['name' => 'b']],
        fn (): array => [DataGrid::make('lines')->schema([TextInput::make('name')->live()])],
    );

    $keys = rowKeys($component);

    $component->set("data.lines.{$keys[1]}.name", 'changed');

    expect(rowKeys($component))->toBe($keys)
        ->and($component->get("data.lines.{$keys[1]}.name"))->toBe('changed');
});
