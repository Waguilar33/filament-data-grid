<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use LogicException;

final class UnsupportedRecordActions extends LogicException
{
    public static function serverSide(string $entry): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] cannot combine recordActions() with serverSide(): an action finds its row among the entry\'s state, which a server-side page does not hold. Use browser mode.',
            $entry,
        ));
    }

    public static function tableContent(string $entry): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] cannot combine recordActions() with toTableContent(): the grid already shows the table\'s recordActions(). Define the actions on the table instead.',
            $entry,
        ));
    }
}
