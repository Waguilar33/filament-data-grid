<?php

declare(strict_types=1);

namespace Workbench\App\Catalog;

final class FakeDispatch
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function products(bool $longNames = false, bool $preSale = false): array
    {
        return [
            ...($longNames ? [self::stocked(7, 'Extra virgin olive oil, cold pressed, family-size tin for restaurants and catering 5 L', 4, [['North · Aisle 2', 6, 2]])] : []),
            self::stocked(1, 'Olive oil 1 L', 12, [['North · Aisle 2', 8, 8], ['South · Main', 10, 4]]),
            self::stocked(2, 'Basmati rice 5 kg', 20, [['North · Aisle 6', 6, 0], ['South · Main', 9, 5], ['East · Shop', 4, 0], ['West · Dock', 12, 0]]),
            self::coded(3, 'Espresso machine', [['South · Main', 'EM-4471'], ['East · Shop', null]]),
            self::stocked(4, 'Paper towels x6', 15, [['North · Aisle 6', 25, 0], ['West · Dock', 3, 0]]),
            self::coded(5, 'Cordless drill', [['North · Aisle 2', 'CD-0193'], ['South · Main', 'CD-0207'], ['West · Dock', 'CD-0215']]),
            self::stocked(6, 'Green tea x20', 9, [['East · Shop', 4, 4], ['South · Main', 7, 5], ['North · Aisle 6', 2, 0]]),
            ...($preSale ? [[...self::coded(8, 'Standing desk', []), 'requested' => 2, 'purchase_order' => 'PO-2026-0003']] : []),
        ];
    }

    /**
     * @param  list<array{0: string, 1: int, 2: int}>  $places
     * @return array<string, mixed>
     */
    private static function stocked(int $id, string $name, int $requested, array $places): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'requested' => $requested,
            'has_codes' => false,
            'places' => array_map(
                fn (array $place): array => ['place' => $place[0], 'available' => $place[1], 'quantity' => $place[2]],
                $places,
            ),
            'units' => [],
        ];
    }

    /**
     * @param  list<array{0: string, 1: ?string}>  $units
     * @return array<string, mixed>
     */
    private static function coded(int $id, string $name, array $units): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'requested' => count($units),
            'has_codes' => true,
            'places' => [],
            'units' => array_map(fn (array $unit): array => ['place' => $unit[0], 'code' => $unit[1]], $units),
        ];
    }
}
