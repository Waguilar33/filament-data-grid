<?php

declare(strict_types=1);

const ACTION_CELLS = '[id="form.lines"]';

function actionCellsDemo(): mixed
{
    return visit('/demo/row-action-cells-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);
}

function quantityAction(int $row): string
{
    return cell($row, 1, ACTION_CELLS).' .fi-data-grid-cell-actions button';
}

it('opens a suffix action from its cell without starting an edit', function (): void {
    actionCellsDemo()
        ->assertMissing(quantityAction(1))
        ->click(quantityAction(0))
        ->assertMissing(ACTION_CELLS.' .slick-cell.editable')
        ->assertMissing(ACTION_CELLS.' .slick-cell.active')
        ->assertSee('Only 4 in stock')
        ->click('Use stock')
        ->assertSeeIn(cell(0, 1, ACTION_CELLS), '4')
        ->assertMissing(quantityAction(0))
        ->assertNoJavaScriptErrors();
});

it('opens a suffix action on a locked cell', function (): void {
    actionCellsDemo()
        ->assertAttributeContains(cell(2, 1, ACTION_CELLS), 'class', 'fi-data-grid-cell-disabled')
        ->click(quantityAction(2))
        ->assertSee('Only 3 in stock')
        ->assertNoJavaScriptErrors();
});

it('runs an action from a group in an Actions cell for its row', function (): void {
    actionCellsDemo()
        ->click(cell(1, 3, ACTION_CELLS).' .fi-dropdown-trigger')
        ->click('.fi-dropdown-panel:visible button:has-text("Mark as gift")')
        ->assertSeeIn(cell(1, 2, ACTION_CELLS), 'Gift')
        ->assertDontSeeIn(cell(0, 2, ACTION_CELLS), 'Gift')
        ->assertNoJavaScriptErrors();
});

it('sizes an Actions column to its content and gives it no spare room', function (): void {
    $page = actionCellsDemo();

    $widths = $page->script(<<<'JS'
        (() => {
            const grid = document.querySelector('[id="form.lines"]')
            const headers = [...grid.querySelectorAll('.slick-header-column')]
            const cell = grid.querySelector('.slick-row .slick-cell.l3')
            const trigger = cell.querySelector('.fi-dropdown-trigger').getBoundingClientRect()

            return [Math.round(headers[3].getBoundingClientRect().width), Math.round(trigger.width), [...grid.querySelectorAll('.slick-viewport')].some((viewport) => viewport.scrollWidth > viewport.clientWidth)]
        })()
        JS);

    expect($widths[0])->toBeLessThanOrEqual(80)
        ->and($widths[0])->toBeGreaterThanOrEqual($widths[1] + 24)
        ->and($widths[2])->toBeFalse();
});
