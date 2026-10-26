<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

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
