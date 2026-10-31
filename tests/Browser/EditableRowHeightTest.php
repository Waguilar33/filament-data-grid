<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

const ALLOCATION = '[id="form.products"]';

function visitAllocation(?int $width = null): AwaitableWebpage
{
    $page = visit('/demo/allocation-demo');

    if ($width !== null) {
        $page->resize($width, 900);
    }

    $page = $page->waitForEvent('networkidle');

    $page->script(<<<'JS'
        async () => {
            for (let tries = 0; document.querySelectorAll('.fi-data-grid-child-host .slick-header').length < 2 && tries < 100; tries++) {
                await new Promise((resolve) => setTimeout(resolve, 50))
            }

            await new Promise((resolve) => setTimeout(resolve, 300))
        }
        JS);

    return $page;
}

function rowFit(AwaitableWebpage $page): mixed
{
    return $page->script(sprintf(<<<'JS'
        () => {
            const grid = document.querySelector(%s)
            const rowsOf = (canvas) => [...canvas.querySelectorAll(':scope .grid-canvas > .slick-row:not(.fi-data-grid-child-placeholder)')]
            const clipped = (row) => [...row.querySelectorAll('.slick-cell')].some((cell) => {
                const content = cell.firstElementChild

                return content && content.getBoundingClientRect().bottom > cell.getBoundingClientRect().bottom + 0.5
            })
            const describe = (row) => ({ text: row.querySelector('.fi-in-text-item')?.innerText.trim(), height: Math.round(row.getBoundingClientRect().height), clipped: clipped(row) })
            const parentCanvas = grid.querySelector('.fi-data-grid-canvas')

            return {
                parents: rowsOf(parentCanvas).map(describe),
                children: [...grid.querySelectorAll('.fi-data-grid-child-host .fi-data-grid-canvas')].flatMap(rowsOf).map(describe),
            }
        }
        JS, json_encode(ALLOCATION)));
}

it('grows editable parent and child rows to fit their tallest cell', function (): void {
    $fit = rowFit(visitAllocation());
    $parents = collect($fit['parents'])->keyBy('text');
    $children = collect($fit['children']);

    expect($parents->pluck('clipped')->unique()->all())->toBe([false])
        ->and($children->pluck('clipped')->unique()->all())->toBe([false])
        ->and($parents['Olive oil 1 L']['height'])->toBeGreaterThan(44)
        ->and($parents->first()['height'])->toBeGreaterThanOrEqual($parents['Olive oil 1 L']['height'])
        ->and($children->pluck('height')->max())->toBeGreaterThan(44);
});

it('measures editable rows again when the grid gets narrower', function (): void {
    $wide = collect(rowFit(visitAllocation())['parents'])->first()['height'];
    $narrow = rowFit(visitAllocation(700));

    expect(collect($narrow['parents'])->first()['height'])->toBeGreaterThan($wide)
        ->and(collect($narrow['parents'])->pluck('clipped')->unique()->all())->toBe([false]);
});

it('keeps editable rows fitted after a live change and after opening and closing a row', function (): void {
    $page = visitAllocation();

    $toggle = ALLOCATION.' .slick-row:has-text("Olive oil 1 L") .fi-data-grid-row-toggle';

    $quantity = ALLOCATION.' .fi-data-grid-child-layer > .fi-data-grid-child-host:nth-of-type(1) .grid-canvas > .slick-row:first-child .slick-cell.l2';

    $page->click($toggle)->wait(0.6)->click($toggle)->wait(0.6)
        ->click($quantity)
        ->keys($quantity.' input', ['3', 'Enter'])
        ->wait(1.5);

    $fit = rowFit($page->wait(1));

    expect(collect($fit['parents'])->pluck('clipped')->unique()->all())->toBe([false])
        ->and(collect($fit['children'])->pluck('clipped')->unique()->all())->toBe([false])
        ->and(collect($fit['parents'])->keyBy('text')['Olive oil 1 L']['height'])->toBeGreaterThan(44);
});
