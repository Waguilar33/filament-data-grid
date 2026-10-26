<?php

declare(strict_types=1);

namespace Workbench\App\Catalog;

final class FakeWarehouses
{
    private const array STOCK = [
        'north-6' => ['zone' => 'North', 'place' => 'Aisle 6', 'available' => 8],
        'north-2' => ['zone' => 'North', 'place' => 'Aisle 2', 'available' => 0],
        'south-main' => ['zone' => 'South', 'place' => 'Main', 'available' => 31],
        'east-shop' => ['zone' => 'East', 'place' => 'Shop', 'available' => 3],
    ];

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(
            fn (array $stock): string => trans_choice('demo::demo.warehouse_option', $stock['available'], [
                'zone' => $stock['zone'],
                'place' => $stock['place'],
            ]),
            self::STOCK,
        );
    }

    public static function isEmpty(string $key): bool
    {
        return (self::STOCK[$key]['available'] ?? 0) === 0;
    }
}
