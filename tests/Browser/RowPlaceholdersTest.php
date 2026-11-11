<?php

declare(strict_types=1);

it('shows each row its own select placeholder, locked or not', function (): void {
    $grid = '[id="form.lines"]';

    visit('/demo/row-placeholders-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED)
        ->assertSeeIn(cell(0, 0, $grid).' .fi-select-input-placeholder', 'Corona · COR-1')
        ->assertAttributeContains(cell(0, 0, $grid), 'class', 'fi-data-grid-cell-disabled')
        ->assertSeeIn(cell(1, 0, $grid).' .fi-select-input-placeholder', 'Wreath · WR-7')
        ->assertScript(sprintf('document.querySelector(%s).textContent', json_encode(cell(2, 0, $grid).' .fi-select-input-placeholder')), '')
        ->assertNoJavaScriptErrors();
});
