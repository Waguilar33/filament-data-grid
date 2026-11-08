<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\DemoOrders;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\DemoOrders\Pages\ListDemoOrders;
use Workbench\App\Filament\Resources\DemoOrders\Pages\ViewDemoOrder;
use Workbench\App\Filament\Resources\DemoOrders\RelationManagers\LinesRelationManager;
use Workbench\App\Filament\Resources\DemoOrders\RelationManagers\LineTotalsRelationManager;
use Workbench\App\Filament\Resources\DemoOrders\RelationManagers\SkeletonLinesRelationManager;
use Workbench\App\Models\DemoOrder;

final class DemoOrderResource extends Resource
{
    protected static ?string $model = DemoOrder::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([TextEntry::make('reference')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('reference')]);
    }

    public static function getRelations(): array
    {
        return [LineTotalsRelationManager::class, LinesRelationManager::class, SkeletonLinesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemoOrders::route('/'),
            'view' => ViewDemoOrder::route('/{record}'),
        ];
    }
}
