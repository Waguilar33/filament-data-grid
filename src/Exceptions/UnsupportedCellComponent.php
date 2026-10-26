<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Exceptions;

use Filament\Schemas\Components\Component;
use LogicException;

final class UnsupportedCellComponent extends LogicException
{
    public static function for(Component $component): self
    {
        return new self(sprintf(
            'DataGrid cells must be form fields or infolist entries, but [%s] was used.',
            $component::class,
        ));
    }
}
