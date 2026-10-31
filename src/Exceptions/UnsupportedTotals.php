<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use LogicException;

final class UnsupportedTotals extends LogicException
{
    public static function serverSide(string $entry): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] cannot combine totals() with serverSide(): the browser only holds the current page, so its totals would leave out the other pages. Use browser mode, or show server-computed totals in the description.',
            $entry,
        ));
    }
}
