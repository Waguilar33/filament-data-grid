<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Rows;

use Waguilar\FilamentDataGrid\DataGrid;

final class EnsureTrailingEmptyRow
{
    public function handle(DataGrid $grid): void
    {
        if (! $grid->hasTrailingEmptyRow()) {
            return;
        }

        $rows = $grid->getRawState();
        $rows = is_array($rows) ? $rows : [];
        $lastKey = array_key_last($rows);

        if ($lastKey !== null && $grid->isTrailingBlankRow($lastKey)) {
            return;
        }

        $newKey = $grid->generateUuid() ?? count($rows);
        $rows[$newKey] = [];

        $grid->rawState($rows);
        $grid->getChildSchema($newKey)?->fill();
    }
}
