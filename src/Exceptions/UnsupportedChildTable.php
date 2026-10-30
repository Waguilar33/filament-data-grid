<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use LogicException;

final class UnsupportedChildTable extends LogicException
{
    public static function serverSide(string $entry): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] cannot combine childTable() with serverSide(): pages fetched by getGridPage() are renderless, so they cannot mount Livewire components. Use browser mode or toTableContent().',
            $entry,
        ));
    }
}
