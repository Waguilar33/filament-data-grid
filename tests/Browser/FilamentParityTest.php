<?php

declare(strict_types=1);

function parityMeasure(int $width): mixed
{
    return visit('/demo/parity-demo')->resize($width, 900)->waitForEvent('networkidle')->wait(0.8)->script(<<<'JS'
        () => {
            const textBox = (element) => {
                const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT)
                let node

                while ((node = walker.nextNode()) && (!node.textContent.trim() || node.parentElement.closest('.fi-sr-only'))) {}

                if (!node) {
                    return null
                }

                const range = document.createRange()
                range.selectNodeContents(node)

                return { rect: range.getBoundingClientRect(), style: getComputedStyle(node.parentElement) }
            }

            const measure = (container, rows, headers) => {
                const box = container.getBoundingClientRect()
                const firstCell = textBox(rows[0].querySelector('[data-parity-first]') ?? rows[0])
                const header = headers.find((cell) => textBox(cell) !== null)
                const headerText = textBox(header)
                const headerStyle = getComputedStyle(header)
                const lastAction = rows[0].querySelector('.fi-ta-actions')?.getBoundingClientRect()

                return {
                    cellX: Math.round(firstCell.rect.left - box.left),
                    cellFont: [firstCell.style.fontSize, firstCell.style.lineHeight, firstCell.style.fontWeight, firstCell.style.color],
                    rowHeights: rows.map((row) => Math.round(row.getBoundingClientRect().height)),
                    headerX: Math.round(headerText.rect.left - box.left),
                    headerHeight: Math.round(rows[0].getBoundingClientRect().top - header.getBoundingClientRect().top),
                    headerPadding: [headerStyle.paddingTop, headerStyle.paddingBottom],
                    headerFont: [headerText.style.fontSize, headerText.style.lineHeight, headerText.style.fontWeight, headerText.style.color],
                    actionEnd: lastAction ? Math.round(Math.max(...[...rows[0].children].map((cell) => cell.getBoundingClientRect().right)) - lastAction.right) : null,
                }
            }

            const native = document.querySelector('.fi-ta-ctn:has(.fi-ta-table)')
            const grid = (id) => document.querySelector(`[id="${id}"] .fi-ta-ctn`)
            const gridMeasure = (id) => measure(
                grid(id),
                [...grid(id).querySelectorAll('.fi-data-grid-parent-row')],
                [...grid(id).querySelectorAll('.slick-header-column')].filter((cell) => !cell.querySelector(':scope > .fi-sr-only')),
            )

            return {
                native: measure(
                    native,
                    [...native.querySelectorAll('.fi-ta-row')],
                    [...native.querySelectorAll('.fi-ta-header-cell')],
                ),
                grid: gridMeasure('content.grid'),
                nested: gridMeasure('content.nested'),
                editable: (() => {
                    const container = document.querySelector('[id="form.editable"] .fi-data-grid-section')

                    return measure(
                        container,
                        [...container.querySelectorAll('.grid-canvas .slick-row')].sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top),
                        [...container.querySelectorAll('.slick-header-column')],
                    )
                })(),
            }
        }
        JS);
}

it('draws an entry grid like a native Filament table', function (int $width): void {
    $sizes = parityMeasure($width);

    expect($sizes['grid'])->toBe($sizes['native']);
})->with([
    'large screen' => 1280,
    'small screen' => 480,
]);

it('draws an entry grid with child rows like a native Filament table, apart from the chevron', function (int $width): void {
    $sizes = parityMeasure($width);

    expect(collect($sizes['nested'])->except(['cellX', 'headerX'])->all())
        ->toBe(collect($sizes['native'])->except(['cellX', 'headerX'])->all());
})->with([
    'large screen' => 1280,
    'small screen' => 480,
]);

it('draws an editable grid like a native Filament table', function (int $width): void {
    $sizes = parityMeasure($width);

    expect(collect($sizes['editable'])->except(['actionEnd'])->all())
        ->toBe(collect($sizes['native'])->except(['actionEnd'])->all());
})->with([
    'large screen' => 1280,
    'small screen' => 480,
]);
