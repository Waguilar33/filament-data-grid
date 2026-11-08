<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\DemoOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class LineTotalsRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Totals';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('product'),
            TextColumn::make('quantity')->numeric(),
        ]);
    }
}
