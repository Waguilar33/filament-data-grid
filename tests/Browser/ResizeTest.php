<?php

declare(strict_types=1);

function columnsOutOfLine(): string
{
    return <<<'JS'
        () => [...document.querySelectorAll('.fi-data-grid-entry-grid .fi-data-grid-canvas')].flatMap((canvas) => {
            const headers = [...canvas.querySelectorAll('.slick-header-column')]
            const rows = [canvas.querySelector('.slick-row'), canvas.querySelector('.slick-footerrow-columns')].filter(Boolean)

            return rows.flatMap((row) => [...row.children].flatMap((cell) => {
                const [, first, last] = cell.className.match(/\bl(\d+) r(\d+)\b/) ?? []
                const start = headers[first]?.getBoundingClientRect()
                const end = headers[last]?.getBoundingClientRect()
                const box = cell.getBoundingClientRect()

                return start && end && (Math.abs(start.left - box.left) > 1 || Math.abs(end.right - box.right) > 1)
                    ? [`${cell.className}: ${Math.round(box.left)}-${Math.round(box.right)} vs ${Math.round(start.left)}-${Math.round(end.right)}`]
                    : []
            }))
        })
        JS;
}

it('keeps headers, rows and totals in line after the grid is resized', function (): void {
    $page = visit('/demo/cash-shift-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);

    foreach ([900, 1200, 1440] as $width) {
        $page->script(sprintf('document.querySelector(".fi-main").style.maxWidth = "%dpx"', $width));
        $page->assertScript(GRID_SETTLED);

        expect($page->script(columnsOutOfLine()))->toBe([])
            ->and($page->script(clippedContentIn('.fi-data-grid-entry-grid')))->toBe([]);
    }
});

it('refits open child rows when the grid is resized', function (): void {
    $page = visit('/demo/delivery-route-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED)
        ->click('[id="content.deliveries"] .fi-data-grid-row-toggle >> nth=0')
        ->assertPresent('[id="content.deliveries"] .fi-data-grid-child-row')
        ->assertScript(GRID_SETTLED);

    foreach ([900, 1200, 1440] as $width) {
        $page->script(sprintf('document.querySelector(".fi-main").style.maxWidth = "%dpx"', $width));
        $page->assertScript(GRID_SETTLED);

        expect($page->script(clippedContentIn('[id="content.deliveries"]')))->toBe([])
            ->and($page->script(scrollsSideways('[id="content.deliveries"]')))->toBeFalse()
            ->and($page->script(<<<'JS'
                () => {
                    const grid = document.querySelector('[id="content.deliveries"] .fi-data-grid-canvas')
                    const child = grid.querySelector('.fi-data-grid-child-row > :last-child').getBoundingClientRect()
                    const header = [...grid.querySelectorAll('.slick-header-column')].at(-1).getBoundingClientRect()

                    return Math.round(header.right - child.right)
                }
                JS))->toBeBetween(0, 40);
    }
});
