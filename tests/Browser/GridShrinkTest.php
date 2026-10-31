<?php

declare(strict_types=1);

it('shrinks a grid back inside its card when the page gets narrower', function (string $url): void {
    $page = visit($url)->waitForEvent('networkidle')->wait(0.5);
    $page->resize(1600, 900)->wait(0.6);
    $page->resize(900, 900)->wait(0.6);

    $overflow = $page->script(<<<'JS'
        () => [...document.querySelectorAll('.fi-data-grid-canvas')].map((canvas) => {
            let root = canvas.closest('.fi-data-grid')

            while (root.parentElement.closest('.fi-data-grid')) {
                root = root.parentElement.closest('.fi-data-grid')
            }

            const card = root.parentElement

            return Math.max(0, Math.round([...canvas.querySelectorAll('.slick-header-column')].at(-1).getBoundingClientRect().right - card.getBoundingClientRect().right))
        })
        JS);

    expect($overflow)->not->toBeEmpty()->each->toBe(0);
})->with([
    'editable grid with a header and child grids' => '/demo/allocation-demo',
    'compact entry grid' => '/demo/order-lines-demo',
    'default entry grid' => '/demo/delivery-route-demo',
    'entry grid in table content' => '/demo/delivery-table-demo',
    'compact entry grid in table content' => '/demo/cash-shift-demo',
]);
