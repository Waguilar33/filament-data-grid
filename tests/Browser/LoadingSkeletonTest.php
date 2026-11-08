<?php

declare(strict_types=1);

const SERVER_DELIVERIES = '[id="content.server_deliveries"]';

function delayLivewireRequests(int $milliseconds): string
{
    return sprintf(<<<'JS'
        () => {
            const fetch = window.fetch
            window.fetch = (...args) => new Promise((resolve) => setTimeout(() => resolve(fetch(...args)), %d))
        }
        JS, $milliseconds);
}

function skeletonBarsIn(string $grid): string
{
    return sprintf(<<<'JS'
        () => [...document.querySelectorAll(%s)].filter((text) => text.getClientRects().length && getComputedStyle(text, '::after').content !== 'none').length
        JS, json_encode("{$grid} .fi-data-grid-canvas .slick-cell .fi-ta-text"));
}

function skeletonFitsGrid(): string
{
    return <<<'JS'
        async () => {
            const html = new DOMParser().parseFromString(await fetch(location.href).then((response) => response.text()), 'text/html')

            return [...document.querySelectorAll('.fi-data-grid-entry-grid')].flatMap((root) => {
                const served = html.getElementById(root.id)?.querySelector('.fi-data-grid-skeleton')
                const canvas = root.querySelector('.fi-data-grid-canvas')

                if (!served || !canvas.offsetParent) {
                    return []
                }

                const holder = document.createElement('div')
                holder.className = 'fi-data-grid-entry-content'
                holder.appendChild(served)
                canvas.parentElement.before(holder)
                const skeleton = served.getBoundingClientRect().height
                holder.remove()
                const grid = canvas.getBoundingClientRect().height

                return Math.abs(skeleton - grid) > 3 ? [`${root.id}: skeleton ${skeleton}, grid ${grid}`] : []
            })
        }
        JS;
}

it('serves skeleton rows only for grids with a loading skeleton', function (): void {
    $cashShift = $this->get('/demo/cash-shift-demo')->assertOk()->getContent();
    $route = $this->get('/demo/delivery-route-demo')->assertOk()->getContent();

    expect(substr_count($cashShift, 'class="fi-data-grid-skeleton '))->toBe(2)
        ->and(substr_count($route, 'class="fi-data-grid-skeleton '))->toBe(1)
        ->and($route)->toMatch('/id="content\.server_deliveries".*?class="fi-data-grid-skeleton /s');
});

it('replaces the skeleton with the grid once it has measured its columns', function (): void {
    $page = visit('/demo/cash-shift-demo')->resize(1440, 900)->waitForEvent('networkidle')->wait(1);

    expect($page->script('document.querySelectorAll(".fi-data-grid-skeleton").length'))->toBe(0)
        ->and($page->script('[...document.querySelectorAll(".fi-data-grid-entry-grid .fi-data-grid-canvas")].map((canvas) => getComputedStyle(canvas).position + " " + getComputedStyle(canvas).opacity)'))->toBe(['relative 1', 'relative 1'])
        ->and($page->script(skeletonFitsGrid()))->toBe([]);
});

it('shows Filament skeleton bars over the rows while a server page loads', function (): void {
    $page = visit('/demo/delivery-route-demo')->resize(1440, 900)->waitForEvent('networkidle')->wait(1);

    $page->script(delayLivewireRequests(1500));
    $page->type(SERVER_DELIVERIES.' .fi-ta-search-field input', 'karen')->wait(0.9);

    expect($page->script(skeletonBarsIn(SERVER_DELIVERIES)))->toBeGreaterThan(0)
        ->and($page->script(sprintf('document.querySelector(%s).classList.contains("fi-data-grid-loading")', json_encode(SERVER_DELIVERIES.' .fi-data-grid-entry-content'))))->toBeFalse();

    $page->wait(2)->assertSeeIn(SERVER_DELIVERIES.' .fi-pagination-overview', 'Showing 1 to 10 of 21 results');

    expect($page->script(skeletonBarsIn(SERVER_DELIVERIES)))->toBe(0);
});

it('waits for Livewire\'s loading delay before showing skeleton bars', function (): void {
    $page = visit('/demo/delivery-route-demo')->resize(1440, 900)->waitForEvent('networkidle')->wait(1);

    $page->script(<<<'JS'
        () => {
            const fetch = window.fetch
            const content = document.querySelector('[id="content.server_deliveries"] .fi-data-grid-entry-content')
            window.__barsSoon = null
            window.fetch = (...args) => {
                setTimeout(() => { window.__barsSoon ??= content.classList.contains('fi-ta-content-loading') }, 100)

                return new Promise((resolve) => setTimeout(() => resolve(fetch(...args)), 1500))
            }
        }
        JS);
    $page->type(SERVER_DELIVERIES.' .fi-ta-search-field input', 'karen')->wait(0.9);

    expect($page->script('window.__barsSoon'))->toBeFalse()
        ->and($page->script(skeletonBarsIn(SERVER_DELIVERIES)))->toBeGreaterThan(0);
});

it('dims a grid without a loading skeleton while a server page loads, as before', function (): void {
    $page = visit('/demo/delivery-route-demo?skeleton=0')->resize(1440, 900)->waitForEvent('networkidle')->wait(1);

    expect($page->content())->not->toContain('fi-data-grid-skeleton');

    $page->script(delayLivewireRequests(1500));
    $page->type(SERVER_DELIVERIES.' .fi-ta-search-field input', 'karen')->wait(0.9);

    expect($page->script(sprintf('document.querySelector(%s).className', json_encode(SERVER_DELIVERIES.' .fi-data-grid-entry-content'))))
        ->toContain('fi-data-grid-loading')
        ->not->toContain('fi-ta-content-loading')
        ->and($page->script(skeletonBarsIn(SERVER_DELIVERIES)))->toBe(0);
});

it('shows skeleton bars in a grid inside a Filament table with a loading skeleton', function (): void {
    $page = visit('/demo/cash-shift-demo')->resize(1440, 900)->waitForEvent('networkidle')->wait(1);

    $page->script(delayLivewireRequests(1500));
    $page->type('.fi-ta-ctn .fi-ta-header-toolbar .fi-ta-search-field input >> nth=0', 'ca')->wait(0.9);

    expect($page->script(skeletonBarsIn('[id="methods"]')))->toBeGreaterThan(0);

    $page->wait(2.5);

    expect($page->script(skeletonBarsIn('[id="methods"]')))->toBe(0);
});
