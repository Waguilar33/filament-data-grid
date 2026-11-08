<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Support;

use BackedEnum;
use Closure;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

final readonly class IconHeader
{
    /**
     * @return Closure(Table): View
     */
    public static function make(string|BackedEnum|Htmlable $icon): Closure
    {
        return fn (Table $table): View => view('filament-data-grid::table-icon-header', ['table' => $table, 'icon' => $icon]);
    }
}
