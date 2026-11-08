<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\DemoOrders\Pages;

use Filament\Resources\Pages\ListRecords;
use Workbench\App\Filament\Resources\DemoOrders\DemoOrderResource;

final class ListDemoOrders extends ListRecords
{
    protected static string $resource = DemoOrderResource::class;
}
