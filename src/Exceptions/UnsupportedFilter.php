<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Exceptions;

use Filament\Tables\Filters\BaseFilter;
use LogicException;

final class UnsupportedFilter extends LogicException
{
    public static function withoutRecords(BaseFilter $filter): self
    {
        return new self(sprintf(
            'DataGridEntry applies SelectFilter and TernaryFilter itself. [%s] (%s) needs records(), which receives the filter state in $filters.',
            $filter->getName(),
            $filter::class,
        ));
    }
}
