<?php

declare(strict_types=1);

namespace Workbench\App\Catalog;

final class FakeWarehouses
{
    private const array STOCK = [
        'z10-b6' => ['zone' => 'Zona 10', 'place' => 'Bodega 6', 'available' => 8],
        'z10-b2' => ['zone' => 'Zona 10', 'place' => 'Bodega 2', 'available' => 0],
        'z4-main' => ['zone' => 'Zona 4', 'place' => 'Principal', 'available' => 31],
        'mix-shop' => ['zone' => 'Mixco', 'place' => 'Tienda', 'available' => 3],
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
