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
            'DataGrid cannot show [%s] as a cell yet. Supported cells: TextInput, Select, Toggle, Checkbox, Hidden and infolist entries.',
            $component::class,
        ));
    }
}
