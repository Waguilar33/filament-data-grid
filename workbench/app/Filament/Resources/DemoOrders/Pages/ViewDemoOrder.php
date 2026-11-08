<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\DemoOrders\Pages;

use Filament\Resources\Pages\ViewRecord;
use Workbench\App\Filament\Resources\DemoOrders\DemoOrderResource;

final class ViewDemoOrder extends ViewRecord
{
    protected static string $resource = DemoOrderResource::class;
}
