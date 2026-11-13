<?php

declare(strict_types=1);

const JOBS = '[id="form.jobs"]';

it('shows a group header before each group of rows', function (): void {
    $page = visit('/demo/jobs-demo')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);

    $lines = $page->script(sprintf(<<<'JS'
        [...document.querySelectorAll('%s .grid-canvas .slick-row')]
            .sort((a, b) => parseFloat(a.style.top) - parseFloat(b.style.top))
            .map((row) => row.classList.contains('fi-data-grid-group-row') ? `# ${row.textContent.trim()}` : row.querySelector('.slick-cell').textContent.trim().split('\n').pop().trim())
        JS, JOBS));

    expect($lines)->toBe([
        '# Sale S-001', 'Garland', 'Wreath',
        '# Order P-003', 'Tree stand',
        '# Sale S-002', 'Light string',
        '# Sale S-003', 'Fraser Fir',
        '# Order P-006', 'Garland',
    ]);
    $page->assertNoJavaScriptErrors();
});

it('collapses a group of a read-only grid from its header or its keyboard-focused toggle', function (): void {
    $page = visit('/demo/jobs-demo?collapsed=1')->resize(1440, 900)->waitForEvent('networkidle')->assertScript(GRID_SETTLED);
    $rows = sprintf('document.querySelectorAll(%s).length', json_encode(JOBS.' .slick-row.fi-data-grid-parent-row'));

    $page->assertScript($rows, 6)
        ->click(JOBS.' .slick-row[data-row="0"] .fi-ta-group-heading')
        ->assertScript($rows, 4)
        ->assertAttribute(JOBS.' .slick-row[data-row="0"] .fi-data-grid-group-toggle', 'aria-expanded', 'false')
        ->assertMissing(JOBS.' .fi-data-grid-group-count')
        ->keys(JOBS.' .slick-row[data-row="0"] .fi-data-grid-group-toggle', 'Enter')
        ->assertScript($rows, 6)
        ->assertNoJavaScriptErrors();
});
