<?php

declare(strict_types=1);

it('renders the demo panel in a browser', function (): void {
    visit('/demo/library-spike')->assertSee('Library Spike')->assertNoJavaScriptErrors();
});
