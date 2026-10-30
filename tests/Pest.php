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

function clippedContentIn(string $scope): string
{
    return sprintf(<<<'JS'
        () => [...document.querySelectorAll(%s)].flatMap((cell) => {
            const box = cell.getBoundingClientRect()
            const elements = [cell, ...cell.querySelectorAll('*')].filter((element) => !element.closest('.fi-sr-only') && element.getClientRects().length > 0)

            return elements
                .filter((element) => {
                    const inner = element.getBoundingClientRect()
                    const overflows = element.clientWidth > 0 && element.scrollWidth > element.clientWidth && getComputedStyle(element).overflowX !== 'visible'

                    return overflows || inner.left < box.left - 0.5 || inner.right > box.right + 0.5
                })
                .map((element) => `${element.getAttribute('class')}: ${element.textContent.trim().slice(0, 40)}`)
        })
        JS, json_encode("{$scope} .slick-header-column, {$scope} .slick-cell, {$scope} .fi-data-grid-child-cell"));
}

function scrollsSideways(string $scope): string
{
    return sprintf(
        '[...document.querySelectorAll(%s)].some((viewport) => viewport.scrollWidth > viewport.clientWidth)',
        json_encode("{$scope} .slick-viewport"),
    );
}

function headerWidth(string $scope, string $label): string
{
    return sprintf(
        '[...document.querySelectorAll(%s)].find((header) => header.textContent.trim() === %s).getBoundingClientRect().width',
        json_encode("{$scope} .slick-header-column"),
        json_encode($label),
    );
}
