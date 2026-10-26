<?php

declare(strict_types=1);

it('loads the grid assets only from the field markup', function (): void {
    $html = $this->get('/demo/data-grid-demo')->assertOk()->getContent();

    expect($html)
        ->not->toMatch('/<link[^>]+data-grid\.css/')
        ->not->toMatch('/<script[^>]+data-grid\.js/')
        ->toContain('x-load-src=')
        ->toMatch('/x-load-css="[^"]*data-grid\.css/');
});
