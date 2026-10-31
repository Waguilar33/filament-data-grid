<?php

declare(strict_types=1);

use Illuminate\Support\Number;
use Pest\Browser\Api\AwaitableWebpage;
use Workbench\App\Catalog\FakeCashShift;

const STANDALONE_TOTALS = '[id="content.standalone_methods"]';
const TABLE_TOTALS = '.fi-data-grid-in-table';
const MONEY_TOTALS = ['money_in' => 3, 'money_out' => 4, 'balance' => 5];

function visitTotals(string $query = ''): AwaitableWebpage
{
    return visit('/demo/cash-shift-demo'.$query)->waitForEvent('networkidle')->wait(0.5);
}

function totalTexts(string $grid): string
{
    return sprintf(
        '[...document.querySelectorAll(%s)].map((cell) => cell.textContent.replace(/\s+/gu, " ").trim())',
        json_encode($grid.' .slick-footerrow-column'),
    );
}

/**
 * @param  list<string>  $methods
 * @return list<string>
 */
function expectedTotals(string $locale, array $methods): array
{
    $rows = array_intersect_key(FakeCashShift::methods(), array_flip($methods));
    $money = fn (string $field): string => (string) Number::currency(array_sum(array_column($rows, $field)), 'USD', $locale);

    return array_map(
        fn (string $text): string => trim((string) preg_replace('/\s+/u', ' ', $text)),
        ['', 'Total', (string) array_sum(array_column($rows, 'movements')), ...array_map($money, array_keys(MONEY_TOTALS))],
    );
}

function textEdges(string $selector): string
{
    return sprintf(<<<'JS'
        (() => {
            const range = document.createRange()
            const node = document.querySelector(%s)
            range.selectNodeContents(node)
            const box = range.getBoundingClientRect()

            return [Math.round(box.left), Math.round(box.right)]
        })()
        JS, json_encode($selector));
}

it('sums every column with a total and formats it like the column, under both grids', function (string $locale): void {
    $page = visitTotals("?locale={$locale}");
    $expected = expectedTotals($locale, ['cash', 'card', 'transfer', 'voucher']);

    if ($locale === 'es') {
        $expected[1] = 'Total';
    }

    expect($page->script(totalTexts(STANDALONE_TOTALS)))->toBe($expected)
        ->and($page->script(totalTexts(TABLE_TOTALS)))->toBe($expected);

    $page->assertNoJavaScriptErrors();
})->with(['en', 'es']);

it('totals only the rows that match the search', function (): void {
    $page = visitTotals()
        ->type(STANDALONE_TOTALS.' .fi-ta-search-field input', 'card')
        ->wait(0.5);

    expect($page->script(totalTexts(STANDALONE_TOTALS)))->toBe(expectedTotals('en', ['card']));

    $page->type(STANDALONE_TOTALS.' .fi-ta-search-field input', '')
        ->wait(0.5);

    expect($page->script(totalTexts(STANDALONE_TOTALS)))->toBe(expectedTotals('en', ['cash', 'card', 'transfer', 'voucher']));
});

it('lines each total up with the values of its column', function (string $grid): void {
    $page = visitTotals();
    $cell = fn (int $column): string => sprintf('%s .slick-row .l%d .fi-in-text-item', $grid, $column);
    $total = fn (int $column): string => sprintf('%s .slick-footerrow-column.l%d .fi-data-grid-total', $grid, $column);

    expect($page->script(textEdges($total(1)))[0])->toBe($page->script(textEdges($cell(1)))[0]);

    foreach ([2, ...MONEY_TOTALS] as $column) {
        expect($page->script(textEdges($total($column)))[1])->toBe($page->script(textEdges($cell($column)))[1]);
    }
})->with([STANDALONE_TOTALS, TABLE_TOTALS]);

it('keeps the totals under the rows while a child table is open', function (): void {
    $page = visitTotals()
        ->click(STANDALONE_TOTALS.' .slick-row:has-text("Card") .fi-data-grid-row-toggle')
        ->wait(1);

    $bottoms = $page->script(sprintf(<<<'JS'
        [%s, %s].map((selector) => Math.round(document.querySelector(selector).getBoundingClientRect().bottom))
        JS, json_encode(STANDALONE_TOTALS.' .grid-canvas'), json_encode(STANDALONE_TOTALS.' .slick-footerrow')));

    expect($bottoms[1])->toBeGreaterThan($bottoms[0])
        ->and($page->script(totalTexts(STANDALONE_TOTALS)))->toBe(expectedTotals('en', ['cash', 'card', 'transfer', 'voucher']));

    $page->assertNoJavaScriptErrors();
});
