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

    public static function childTable(string $entry): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] cannot combine childRecordActions() with childTable(): child row actions belong to the nested table RepeatableEntry\'s rows, which childTable() replaces. Define the actions in the child table component instead.',
            $entry,
        ));
    }

    public static function withoutChildRows(string $entry): self
    {
        return new self(sprintf(
            'DataGridEntry [%s] has childRecordActions() but its schema has no nested RepeatableEntry with table() to show child rows.',
            $entry,
        ));
    }
}
