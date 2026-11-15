<?php

declare(strict_types=1);

const DELETE_ACTION_FITS = <<<'JS'
    (() => {
        const cell = document.querySelector('[id="form.lines"] .slick-row .fi-data-grid-actions-cell')
        const action = cell.querySelector('.fi-data-grid-inline-actions')

        return action.scrollWidth <= action.clientWidth && action.getBoundingClientRect().right < cell.getBoundingClientRect().right
    })()
    JS;

it('fits the delete action in its column', function (string $query): void {
    $page = visit('/demo/delete-action-demo'.$query)->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);

    if ($query !== '') {
        $page->click('Add to lines')->assertPresent('[id="form.lines"] .slick-row .fi-data-grid-inline-actions')->wait(0.5);
    }

    expect($page->script(DELETE_ACTION_FITS))->toBeTrue();
})->with([
    'with rows from the start' => '',
    'after adding the first row to an empty grid' => '?empty=1',
]);
