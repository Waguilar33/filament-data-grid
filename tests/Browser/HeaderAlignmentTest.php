<?php

declare(strict_types=1);

it('draws a compact grid header exactly like a compact section header', function (): void {
    $page = visit('/demo/order-lines-demo')->waitForEvent('networkidle');

    $measure = fn (string $header): mixed => $page->script(sprintf(<<<'JS'
        () => {
            const header = document.querySelector(%s)
            const card = header.closest('section').getBoundingClientRect()

            return [
                getComputedStyle(header).padding,
                header.getBoundingClientRect().height,
                header.querySelector('svg').getBoundingClientRect().left - card.left,
                header.querySelector('h2, h3').getBoundingClientRect().left - card.left,
                getComputedStyle(header.nextElementSibling).borderTopWidth,
                getComputedStyle(header.closest('section')).borderRadius,
            ]
        }
        JS, json_encode($header)));

    expect($measure('.fi-data-grid-section > .fi-section-header'))
        ->toBe($measure('.fi-section.fi-compact:not(.fi-data-grid-section) > .fi-section-header'));
});

it('draws grid and table icon headers exactly like a Filament section header', function (): void {
    $measure = fn (string $url, string $header): mixed => visit($url)->resize(1280, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED)->script(sprintf(<<<'JS'
        () => {
            const header = document.querySelector(%s)
            const box = header.getBoundingClientRect()
            const offset = (selector) => {
                const rect = header.querySelector(selector).getBoundingClientRect()

                return [Math.round(rect.left - box.left), Math.round(rect.top - box.top), Math.round(rect.width), Math.round(rect.height)]
            }

            return {
                padding: getComputedStyle(header).padding,
                icon: offset('svg'),
                heading: offset('.fi-section-header-heading'),
                description: offset('.fi-section-header-description').slice(0, 2),
                headingFont: getComputedStyle(header.querySelector('.fi-section-header-heading')).font,
                descriptionFont: getComputedStyle(header.querySelector('.fi-section-header-description')).font,
            }
        }
        JS, json_encode($header)));

    $section = $measure('/demo/delivery-table-demo', '.fi-section:not(.fi-compact) > .fi-section-header');
    $table = $measure('/demo/delivery-table-demo', '.fi-data-grid-icon-header');
    $entry = $measure('/demo/delivery-route-demo', '[id="content.deliveries"] .fi-data-grid-icon-header');

    $withoutTextWidths = fn (array $header): array => [...$header, 'heading' => array_slice($header['heading'], 0, 2)];

    expect($withoutTextWidths($table))->toBe($withoutTextWidths($section))
        ->and($withoutTextWidths($entry))->toBe($withoutTextWidths($section));
});

it('lines a compact grid table header up with a compact section', function (): void {
    $header = visit('/demo/cash-shift-demo')->waitForEvent('networkidle')->script(<<<'JS'
        () => {
            const header = document.querySelector('.fi-ta-ctn:has(.fi-data-grid-compact) .fi-ta-header')
            const card = header.closest('.fi-ta-ctn').getBoundingClientRect()

            return [
                getComputedStyle(header).paddingTop,
                header.querySelector('svg').getBoundingClientRect().left - card.left,
                header.querySelector('.fi-section-header-heading').getBoundingClientRect().left - card.left,
                getComputedStyle(header.closest('.fi-ta-ctn')).borderRadius,
            ]
        }
        JS);

    expect($header)->toBe(['10px', 16, 52, '8px']);
});

function gridEdges(string $url, string $card, ?int $width = null): mixed
{
    $page = visit($url);

    if ($width !== null) {
        $page->resize($width, 900);
    }

    return $page->waitForEvent('networkidle')->assertScript(GRID_SETTLED)->script(sprintf(<<<'JS'
        () => {
            const card = document.querySelector(%s)
            const { left, right } = card.getBoundingClientRect()
            const textBoxes = (cell) => {
                const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT)
                const boxes = []
                let node

                while ((node = walker.nextNode())) {
                    if (node.textContent.trim() && !node.parentElement.closest('.fi-sr-only')) {
                        const range = document.createRange()
                        range.selectNodeContents(node)
                        boxes.push(range.getBoundingClientRect())
                    }
                }

                return [...boxes, ...[...cell.querySelectorAll('svg')].map((svg) => svg.getBoundingClientRect())].filter((box) => box.width > 0)
            }
            const contentEnd = (cell) => {
                const link = cell.querySelector('.fi-data-grid-record-link svg')

                if (link) {
                    return link.getBoundingClientRect().right
                }

                const padded = cell.querySelector(':scope > .fi-ta-col > .fi-ta-text') ?? cell

                return padded.getBoundingClientRect().right - parseFloat(getComputedStyle(padded).paddingRight)
            }
            const contentStart = (cell) => Math.round(Math.min(...textBoxes(cell).map((box) => box.left)) - left)
            const grid = card.querySelector('.fi-data-grid-canvas')
            const row = grid.querySelector('.fi-data-grid-parent-row') ?? grid.querySelector('.grid-canvas .slick-row')
            const last = [...row.querySelectorAll('.slick-cell')].at(-1)

            return {
                icon: Math.round(card.querySelector('.fi-section-header > svg, .fi-ta-header svg').getBoundingClientRect().left - left),
                title: Math.round(card.querySelector('.fi-section-header-heading, .fi-ta-header-heading').getBoundingClientRect().left - left),
                                header: contentStart([...grid.querySelectorAll('.slick-header-column')].find((cell) => textBoxes(cell).length > 0)),
                cell: contentStart([...row.querySelectorAll('.slick-cell')].find((cell) => !cell.classList.contains('fi-data-grid-toggle-cell'))),
                lastEnd: Math.round(right - contentEnd(last)),
            }
        }
        JS, json_encode($card)));
}

it('starts a compact grid’s columns on the header icon line', function (): void {
    expect(gridEdges('/demo/order-lines-demo', '.fi-data-grid-section'))
        ->toBe(['icon' => 16, 'title' => 52, 'header' => 16, 'cell' => 16, 'lastEnd' => 16]);
});

it('puts a compact table grid’s chevron on the icon line and its first column on the title line', function (): void {
    expect(gridEdges('/demo/cash-shift-demo', '.fi-ta-ctn:has(.fi-data-grid-compact)'))
        ->toBe(['icon' => 16, 'title' => 52, 'header' => 52, 'cell' => 52, 'lastEnd' => 16]);
});

it('starts a default grid’s columns on the header icon line, like a Filament table', function (): void {
    expect(gridEdges('/demo/delivery-route-demo', '[id="content.deliveries"] .fi-ta-ctn'))
        ->toBe(['icon' => 24, 'title' => 60, 'header' => 60, 'cell' => 60, 'lastEnd' => 24]);
});

it('starts a default table grid’s columns on the header icon line', function (): void {
    expect(gridEdges('/demo/delivery-table-demo', '.fi-ta-ctn:has(.fi-data-grid-in-table)'))
        ->toBe(['icon' => 24, 'title' => 60, 'header' => 60, 'cell' => 60, 'lastEnd' => 24]);
});

it('narrows a default grid’s insets to 16 pixels on small screens, like a Filament table', function (): void {
    expect(gridEdges('/demo/delivery-route-demo', '[id="content.deliveries"] .fi-ta-ctn', width: 600))
        ->toMatchArray(['icon' => 16, 'header' => 52, 'cell' => 52]);
});

function glyphEdges(string $url, string $card): mixed
{
    return visit($url)->waitForEvent('networkidle')->assertScript(GRID_SETTLED)->script(sprintf(<<<'JS'
        async () => {
            for (let tries = 0; !document.querySelector(%1$s)?.querySelector('.fi-data-grid-row-toggle:not(.fi-data-grid-row-toggle-expanded) svg') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }

            const card = document.querySelector(%1$s)
            const left = card.getBoundingClientRect().left
            const glyphLeft = (svg) => Math.min(...[...svg.querySelectorAll('path, circle, rect, line, polyline, polygon, ellipse')].map((shape) => shape.getBoundingClientRect().left))
            const header = card.querySelector('.fi-section-header > svg, .fi-ta-header svg')
            const chevron = card.querySelector('.fi-data-grid-canvas .fi-data-grid-row-toggle:not(.fi-data-grid-row-toggle-expanded) svg')

            return Math.round(Math.abs(glyphLeft(chevron) - glyphLeft(header)) * 10) / 10 + 0
        }
        JS, json_encode($card)));
}

it('starts the drawn chevron where the drawn header icon starts', function (string $url, string $card): void {
    expect(glyphEdges($url, $card))->toBeLessThanOrEqual(0.5);
})->with([
    'standalone' => ['/demo/delivery-route-demo', '[id="content.deliveries"] .fi-ta-ctn'],
    'table' => ['/demo/delivery-table-demo', '.fi-ta-ctn:has(.fi-data-grid-in-table)'],
    'compact table' => ['/demo/cash-shift-demo', '.fi-ta-ctn:has(.fi-data-grid-compact)'],
    'editable compact' => ['/demo/allocation-demo', '.fi-data-grid-section'],
]);

it('lines an editable grid with a header up like an entry grid', function (): void {
    expect(gridEdges('/demo/allocation-demo', '.fi-data-grid-section'))
        ->toMatchArray(['icon' => 16, 'title' => 52, 'header' => 52, 'cell' => 52, 'lastEnd' => 16]);
});

it('centers the drawn expanded chevron on its row toggle', function (string $url, string $card): void {
    $offset = visit($url)->waitForEvent('networkidle')->assertScript(GRID_SETTLED)->script(sprintf(<<<'JS'
        async () => {
            const card = () => document.querySelector(%1$s)
            const expanded = () => card()?.querySelector('.fi-data-grid-canvas .fi-data-grid-row-toggle-expanded svg')

            for (let tries = 0; !card()?.querySelector('.fi-data-grid-row-toggle svg') && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }

            if (!expanded()) {
                card().querySelector('.fi-data-grid-canvas .fi-data-grid-row-toggle').click()
            }

            for (let tries = 0; !expanded() && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }

            const svg = expanded()
            const shapes = [...svg.querySelectorAll('path, circle, rect, line, polyline, polygon, ellipse')].map((shape) => shape.getBoundingClientRect())
            const drawnCenter = (Math.min(...shapes.map((box) => box.top)) + Math.max(...shapes.map((box) => box.bottom))) / 2
            const button = svg.closest('.fi-data-grid-row-toggle').getBoundingClientRect()

            return Math.round(Math.abs(drawnCenter - (button.top + button.height / 2)) * 10) / 10 + 0
        }
        JS, json_encode($card)));

    expect($offset)->toBeLessThanOrEqual(0.5);
})->with([
    'standalone' => ['/demo/delivery-route-demo', '[id="content.deliveries"] .fi-ta-ctn'],
    'table' => ['/demo/delivery-table-demo', '.fi-ta-ctn:has(.fi-data-grid-in-table)'],
    'compact table' => ['/demo/cash-shift-demo', '.fi-ta-ctn:has(.fi-data-grid-compact)'],
    'editable compact' => ['/demo/allocation-demo', '.fi-data-grid-section'],
]);
