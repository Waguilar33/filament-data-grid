<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use Filament\Schemas\Components\Component;
use LogicException;

final class UnsupportedCellComponent extends LogicException
{
    public static function for(Component $component): self
    {
        return new self(sprintf(
            'DataGrid cells must be form fields, infolist entries or Actions, but [%s] was used.',
            $component::class,
        ));
    }

    public static function actionsWithoutKey(): self
    {
        return new self('An Actions cell in a DataGrid needs a key(), which names its column, for example Actions::make([...])->key(\'lineActions\').');
    }
}
