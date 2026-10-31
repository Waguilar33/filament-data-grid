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

it('lines a compact grid table header up with a compact section', function (): void {
    $header = visit('/demo/cash-shift-demo')->waitForEvent('networkidle')->script(<<<'JS'
        () => {
            const header = document.querySelector('.fi-ta-ctn:has(.fi-data-grid-compact) .fi-ta-header')
            const card = header.closest('.fi-ta-ctn').getBoundingClientRect()

            return [
                getComputedStyle(header).paddingTop,
                header.querySelector('svg').getBoundingClientRect().left - card.left,
                header.querySelector('.fi-ta-header-heading').getBoundingClientRect().left - card.left,
                getComputedStyle(header.closest('.fi-ta-ctn')).borderRadius,
            ]
        }
        JS);

    expect($header)->toBe(['10px', 16, 52, '8px']);
});

function compactEdges(string $url, string $card): mixed
{
    return visit($url)->waitForEvent('networkidle')->wait(0.5)->script(sprintf(<<<'JS'
        () => {
            const card = document.querySelector(%s)
            const { left, right } = card.getBoundingClientRect()
            const contentStart = (cell) => Math.round(cell.getBoundingClientRect().left + parseFloat(getComputedStyle(cell).paddingLeft) - left)
            const grid = card.querySelector('.fi-data-grid-canvas')
            const row = grid.querySelector('.fi-data-grid-parent-row')
            const chevron = row.querySelector('.fi-data-grid-row-toggle svg')
            const actions = row.querySelector('.fi-data-grid-record-actions')

            return {
                icon: Math.round(card.querySelector('.fi-section-header > svg, .fi-ta-header svg').getBoundingClientRect().left - left),
                title: Math.round(card.querySelector('.fi-section-header-heading, .fi-ta-header-heading').getBoundingClientRect().left - left),
                chevron: chevron ? Math.round(chevron.getBoundingClientRect().left - left) : null,
                header: contentStart([...grid.querySelectorAll('.slick-header-column')].find((cell) => !cell.querySelector('.fi-sr-only'))),
                cell: contentStart([...row.querySelectorAll('.slick-cell')].find((cell) => !cell.classList.contains('fi-data-grid-toggle-cell'))),
                actionsEnd: actions ? Math.round(right - actions.getBoundingClientRect().right) : null,
            }
        }
        JS, json_encode($card)));
}

it('starts a compact grid’s columns on the header icon line', function (): void {
    expect(compactEdges('/demo/order-lines-demo', '.fi-data-grid-section'))
        ->toBe(['icon' => 16, 'title' => 52, 'chevron' => null, 'header' => 16, 'cell' => 16, 'actionsEnd' => 16]);
});

it('puts a compact table grid’s chevron on the icon line and its first column on the title line', function (): void {
    expect(compactEdges('/demo/cash-shift-demo', '.fi-ta-ctn:has(.fi-data-grid-compact)'))
        ->toBe(['icon' => 16, 'title' => 52, 'chevron' => 16, 'header' => 52, 'cell' => 52, 'actionsEnd' => null]);
});
