<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Rows;

final class IsRowBlank
{
    /**
     * @param  array<array-key, mixed>  $row
     * @param  list<string>  $fields
     */
    public function handle(array $row, array $fields): bool
    {
        foreach ($fields as $field) {
            if (filled($row[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
