<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGrid;
use Waguilar\FilamentDataGrid\Tests\Fixtures\LinesForm;
use Waguilar\FilamentDataGrid\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Browser');

pest()->beforeEach(function (): void {
    View::addNamespace('filament-data-grid-tests', __DIR__.'/Fixtures/views');
    LinesForm::$initialLines = [];
    LinesForm::$configureUsing = null;
})->in('Feature');

/**
 * @param  list<array<string, mixed>>  $lines
 * @param  Closure(): list<mixed>  $components
 */
function mountLines(array $lines, Closure $components): Testable
{
    LinesForm::$initialLines = $lines;
    LinesForm::$configureUsing = $components;

    return Livewire::test(LinesForm::class);
}

function grid(Testable $component, string $name = 'lines'): DataGrid
{
    $grid = $component->instance()->form->getComponent($name);

    expect($grid)->toBeInstanceOf(DataGrid::class);

    return $grid;
}

/**
 * @return list<string>
 */
function rowKeys(Testable $component, string $name = 'lines'): array
{
    return array_map(strval(...), array_keys($component->get("data.{$name}") ?? []));
}

/**
 * @param  array<string, mixed>  $payload
 */
function rowActionsHtml(array $payload, int $row): ?string
{
    $id = $payload['rows'][$row]['actions'] ?? null;

    if ($id === null) {
        return null;
    }

    return str_replace($payload['rowKeyPlaceholder'], $payload['rows'][$row]['key'], $payload['actionTemplates'][$id]);
}
