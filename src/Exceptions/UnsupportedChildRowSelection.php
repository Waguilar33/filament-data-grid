<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use LogicException;

final class UnsupportedChildRowSelection extends LogicException
{
    public static function withoutChildRows(string $field): self
    {
        return new self(sprintf(
            'DataGridCheckboxList [%s] uses selectChildRows() but its schema has no child rows. Add a RepeatableEntry with a table() to the schema; its rows are the ones that get checkboxes.',
            $field,
        ));
    }

    public static function grouped(string $field): self
    {
        return new self(sprintf(
            'DataGridCheckboxList [%s] cannot combine selectChildRows() with defaultGroup(): each parent row already groups its child rows.',
            $field,
        ));
    }

    public static function duplicateKey(string $field, string $key): self
    {
        return new self(sprintf(
            'DataGridCheckboxList [%s] has two child rows with the key [%s]. Child keys are the selected values, so they must be unique across all parent rows: use model keys, or key each child array by a unique id.',
            $field,
            $key,
        ));
    }
}
