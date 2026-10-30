<?php

declare(strict_types=1);

namespace Workbench\App\Catalog;

final class FakeDeliveries
{
    private const CUSTOMERS = ['Ana Torres', 'Bruno Díaz', 'Carla Méndez', 'Diego Rojas', 'Elena Vargas', 'Felipe Castro', 'Gabriela Ruiz', 'Hugo Navarro', 'Inés Herrera', 'Julián Morales', 'Karen Ortiz'];

    /**
     * @return list<string>
     */
    public static function customers(): array
    {
        return self::CUSTOMERS;
    }

    /**
     * @return list<array{delivery_number: string, customer: string, fill_status: string, kind: string, holds: list<string>, lines: list<array{product: string, code: string|null, requested: int, handed_over: int}>}>
     */
    public static function all(int $count = 23): array
    {
        $deliveries = [];

        for ($index = 1; $index <= $count; $index++) {
            $lines = [];

            for ($line = 0; $line < ($index % 5) + 1; $line++) {
                $product = FakeProducts::all()[($index * 7 + $line * 13) % 200];
                $requested = (($index + $line) % 6) + 1;

                $lines[] = [
                    'product' => $product['name'],
                    'code' => ($index + $line) % 3 === 0 ? sprintf('U-%04d', $index * 10 + $line) : null,
                    'requested' => $requested,
                    'handed_over' => ($index % 4 === 0 && $line === 0) ? $requested - 1 : $requested,
                ];
            }

            $isComplete = collect($lines)->every(fn (array $line): bool => $line['handed_over'] >= $line['requested']);

            $deliveries[] = [
                'delivery_number' => sprintf('DLV-2026-%07d', $index),
                'customer' => self::CUSTOMERS[$index % count(self::CUSTOMERS)],
                'fill_status' => $isComplete ? 'complete' : 'partial',
                'kind' => $index % 3 === 0 ? 'pickup' : 'shipment',
                'holds' => match ($index % 4) {
                    0 => ['awaiting_stock', 'awaiting_payment'],
                    1 => ['awaiting_stock'],
                    default => ['ready'],
                },
                'lines' => $lines,
            ];
        }

        return $deliveries;
    }
}
