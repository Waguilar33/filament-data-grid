<?php

declare(strict_types=1);

it('reserves the height the grid draws, totals row included, before the grid loads', function (string $url): void {
    $page = visit($url)->resize(1440, 900)->waitForEvent('networkidle')->wait(1);

    $mismatches = $page->script(<<<'JS'
        async () => {
            const html = new DOMParser().parseFromString(await fetch(location.href).then((response) => response.text()), 'text/html')

            return [...html.querySelectorAll('.fi-data-grid-entry-grid')].flatMap((served) => {
                const live = document.getElementById(served.id)?.querySelector('.fi-data-grid-canvas')
                const probe = document.body.appendChild(served.querySelector('.fi-data-grid-canvas').cloneNode())
                const reserved = parseFloat(getComputedStyle(probe).minHeight)
                probe.remove()
                const drawn = live?.getBoundingClientRect().height

                return live?.offsetParent && Math.abs(reserved - drawn) > 1 ? [`${served.id}: reserved ${reserved}, drawn ${drawn}`] : []
            })
        }
        JS);

    expect($mismatches)->toBe([]);
})->with(['/demo/cash-shift-demo', '/demo/parity-demo', '/demo/order-lines-demo']);
