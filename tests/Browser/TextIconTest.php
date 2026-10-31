<?php

declare(strict_types=1);

use Pest\Browser\Api\AwaitableWebpage;

function iconGaps(AwaitableWebpage $page, string $scope): mixed
{
    return $page->script(sprintf(<<<'JS'
        () => [...document.querySelectorAll(%s)].map((item) => {
            const icon = item.querySelector(':scope > .fi-icon').getBoundingClientRect()
            const glyphs = []
            const walker = document.createTreeWalker(item, NodeFilter.SHOW_TEXT)

            for (let node = walker.nextNode(); node; node = walker.nextNode()) {
                for (let index = 0; index < node.textContent.length; index++) {
                    if (node.textContent[index].trim()) {
                        const range = document.createRange()
                        range.setStart(node, index)
                        range.setEnd(node, index + 1)
                        glyphs.push(range.getBoundingClientRect())
                    }
                }
            }

            const isAfter = glyphs[0].left < icon.left

            return {
                cell: item.closest('.fi-data-grid-child-cell') ? 'child' : (item.closest('.slick-cell') ? 'parent' : 'outside'),
                position: isAfter ? 'after' : 'before',
                gap: Math.round(isAfter ? icon.left - glyphs.at(-1).right : glyphs[0].left - icon.right),
            }
        })
        JS, json_encode($scope.' .fi-in-text-item:has(> .fi-icon)')));
}

it('spaces a text entry icon in a grid cell like the same entry outside the grid', function (): void {
    $gaps = iconGaps(visit('/demo/order-lines-demo')->waitForEvent('networkidle')->wait(0.5), 'main');

    $outside = collect($gaps)->firstWhere('cell', 'outside');
    $parents = collect($gaps)->where('cell', 'parent');

    expect($outside['gap'])->toBeGreaterThan(0)
        ->and($parents)->not->toBeEmpty()
        ->and($parents->pluck('gap')->unique()->all())->toBe([$outside['gap']]);
});

it('spaces text entry icons the same in parent and child cells, before and after the text', function (): void {
    $page = visit('/demo/delivery-route-demo')->waitForEvent('networkidle')->wait(0.5);
    $page->script("() => document.querySelector('[id=\"content.deliveries\"] .fi-data-grid-row-toggle').click()");

    $gaps = collect(iconGaps($page->wait(0.5), '[id="content.deliveries"]'));

    expect($gaps->pluck('cell')->unique()->sort()->values()->all())->toBe(['child', 'parent'])
        ->and($gaps->pluck('position')->unique()->sort()->values()->all())->toBe(['after', 'before'])
        ->and($gaps->pluck('gap')->unique()->all())->toBe([$gaps->firstWhere('cell', 'parent')['gap']])
        ->and($gaps->first()['gap'])->toBeGreaterThan(0);
});
