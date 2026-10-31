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
