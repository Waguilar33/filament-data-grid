<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\View;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Pest\Browser\Api\AwaitableWebpage;
use Waguilar\FilamentDataGrid\DataGrid;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Tests\Fixtures\EntryPage;
use Waguilar\FilamentDataGrid\Tests\Fixtures\LinesForm;
use Waguilar\FilamentDataGrid\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Browser');

const TABLE_ROW_HEIGHT = 57;

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

function openDropdownLift(string $actions): string
{
    return sprintf(<<<'JS'
        () => {
            const panel = [...document.querySelectorAll(%s)].find((element) => element.style.display !== 'none')
            const row = panel.closest('.slick-row')

            return `${getComputedStyle(row).zIndex}/${getComputedStyle(row.closest('.fi-data-grid')).zIndex}`
        }
        JS, json_encode("{$actions} .fi-dropdown-panel"));
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

const LINES = '[id="form.lines"]';

function cell(int $row, int $column, string $grid = LINES): string
{
    return sprintf('%s .slick-row[style*="top: %dpx"] .slick-cell.l%d', $grid, $row * TABLE_ROW_HEIGHT, $column);
}

function savedState(string $grid = 'lines'): string
{
    return sprintf('JSON.parse(document.querySelector("[data-saved-state]").textContent).%s', $grid);
}

function visitDemo(string $query = ''): AwaitableWebpage
{
    $page = visit('/demo/data-grid-demo'.$query)->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            const isDrawn = () => [...document.querySelectorAll('.fi-data-grid-canvas')].every((canvas) => canvas.querySelector('.slick-header'))

            for (let tries = 0; !isDrawn() && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

const ROUTE = '[id="content.deliveries"]';

function visitRoute(string $query = ''): AwaitableWebpage
{
    $page = visit('/demo/delivery-route-demo'.$query)->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; !document.querySelector('.fi-data-grid-canvas .slick-header') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

function visitDispatch(): AwaitableWebpage
{
    $page = visit('/demo/dispatch-demo')->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; document.querySelectorAll('.fi-data-grid-child-host .slick-header').length < 3 && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }
        }
        JS);

    return $page;
}

const COUNTS = '[id="form.counts"]';

const COUNTED = 2;

function countedInput(int $row): string
{
    return cell($row, COUNTED, COUNTS).' input';
}

/**
 * @return list<array{number: string, customer: string}>
 */
function manyDeliveries(int $count): array
{
    return array_map(
        fn (int $index): array => ['number' => sprintf('DLV-%02d', $index), 'customer' => $index % 2 === 0 ? 'Ana' : 'Bruno'],
        range(1, $count),
    );
}

function serverEntry(Closure $configure): DataGridEntry
{
    EntryPage::$configureUsing = fn (): array => [
        $configure(DataGridEntry::make('deliveries')->schema([TextEntry::make('number'), TextEntry::make('customer')])),
    ];

    $entry = Livewire::test(EntryPage::class)->instance()->getSchema('infolist')?->getComponent('deliveries');

    expect($entry)->toBeInstanceOf(DataGridEntry::class);

    return $entry;
}
