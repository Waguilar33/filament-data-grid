<?php

declare(strict_types=1);

it('serves the demo panel', function (): void {
    $this->get('/demo/library-spike')->assertOk();
});
