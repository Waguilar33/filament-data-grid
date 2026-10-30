<?php

declare(strict_types=1);

it('loads the grid stylesheet with the page and its script only from the field markup', function (): void {
    $html = $this->get('/demo/data-grid-demo')->assertOk()->getContent();

    expect($html)
        ->toMatch('/<link[^>]+data-grid\.css/')
        ->not->toMatch('/<script[^>]+data-grid\.js/')
        ->toMatch('/x-load-src="[^"]*data-grid\.js/');
});
