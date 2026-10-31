<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use LogicException;

final class DuplicateRecordActionName extends LogicException
{
    public static function make(string $entry, string $action): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] has a recordActions() and a childRecordActions() action both named [%s]. Filament finds an action by its name, so give each a unique name, for example [edit] and [editChild].',
            $entry,
            $action,
        ));
    }
}
