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
