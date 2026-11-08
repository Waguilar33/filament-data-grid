<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\DemoOrders\RelationManagers;

final class SkeletonLinesRelationManager extends LinesRelationManager
{
    protected static ?string $title = 'Lines with a skeleton';

    protected bool $hasLoadingSkeleton = true;
}
