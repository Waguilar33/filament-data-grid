<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use Filament\Schemas\Schema;

final readonly class EntryPage
{
    /**
     * @param  array<string, Schema>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int|string|null $recordsPerPage,
    ) {}
}
