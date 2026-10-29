<?php

declare(strict_types=1);

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Support\Arr;
use Waguilar\FilamentDataGrid\DataGrid;

dataset('translation files', [
    'package' => [__DIR__.'/../../resources/lang', 'data-grid.php'],
    'workbench demo' => [__DIR__.'/../../workbench/lang', 'demo.php'],
]);

it('has the same keys in English and Spanish', function (string $directory, string $file): void {
    $english = Arr::dot(require "{$directory}/en/{$file}");
    $spanish = Arr::dot(require "{$directory}/es/{$file}");

    expect(array_keys($spanish))->toEqualCanonicalizing(array_keys($english));
})->with('translation files');

it('renders the grid text in Spanish', function (): void {
    app()->setLocale('es');

    expect(__('filament-data-grid::data-grid.errors.row', ['row' => 2]))->toBe('Fila 2');
});

it('sends only translated text to radio and toggle button selects', function (string $locale): void {
    app()->setLocale($locale);

    $component = mountLines([], fn (): array => [
        DataGrid::make('lines')->schema([
            Radio::make('kind')->options(['a' => 'A']),
            ToggleButtons::make('tags')->options(['a' => 'A'])->multiple(),
        ]),
    ]);

    $texts = collect(grid($component)->getGridPayload()['columns'])
        ->flatMap(fn (array $column): array => array_values($column['editor']['select']))
        ->filter(fn (mixed $text): bool => is_string($text));

    expect($texts->filter(fn (string $text): bool => str_contains($text, '::'))->values()->all())->toBe([]);
})->with(['en', 'es']);
