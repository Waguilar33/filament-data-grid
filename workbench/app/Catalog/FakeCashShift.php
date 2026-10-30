<?php

declare(strict_types=1);

namespace Workbench\App\Catalog;

final class FakeCashShift
{
    private const METHODS = ['cash' => 34, 'card' => 27, 'transfer' => 12, 'voucher' => 3];

    /**
     * @return array<string, array{key: string, movements: int, money_in: float, money_out: float, balance: float}>
     */
    public static function methods(): array
    {
        $methods = [];

        foreach (array_keys(self::METHODS) as $method) {
            $movements = self::movements($method);
            $in = array_sum(array_filter(array_column($movements, 'amount'), fn (float $amount): bool => $amount > 0));
            $out = -array_sum(array_filter(array_column($movements, 'amount'), fn (float $amount): bool => $amount < 0));

            $methods[$method] = [
                'key' => $method,
                'movements' => count($movements),
                'money_in' => round($in, 2),
                'money_out' => round($out, 2),
                'balance' => round($in - $out, 2),
            ];
        }

        return $methods;
    }

    /**
     * @return array<string, array{id: string, time: string, type: string, reference: string, details: string|null, amount: float}>
     */
    public static function movements(string $method): array
    {
        $count = self::METHODS[$method] ?? 0;
        $types = $method === 'cash' ? ['sale', 'sale', 'sale', 'refund', 'cash_in', 'cash_out'] : ['sale', 'sale', 'sale', 'sale', 'refund'];
        $offset = array_search($method, array_keys(self::METHODS), true) * 100;
        $movements = [];

        for ($index = 1; $index <= $count; $index++) {
            $type = $types[($index * 5) % count($types)];
            $amount = round(((($index * 37 + $offset) % 180) + 5) + (($index * 13) % 100) / 100, 2);
            $id = sprintf('MOV-%04d', $offset + $index);

            $movements[$id] = [
                'id' => $id,
                'time' => sprintf('%02d:%02d', 8 + intdiv($index * 11, 60) % 10, ($index * 11) % 60),
                'type' => $type,
                'reference' => sprintf('R-%04d', 1000 + $offset + $index),
                'details' => match ($method) {
                    'card' => sprintf('%s •••• %04d', ['Visa', 'Mastercard', 'Amex'][$index % 3], ($index * 1979) % 10000),
                    'transfer' => sprintf('Bank ref %05d', ($index * 7919) % 100000),
                    'voucher' => sprintf('V-%03d', $index * 17),
                    default => null,
                },
                'amount' => in_array($type, ['refund', 'cash_out'], true) ? -$amount : $amount,
            ];
        }

        return $movements;
    }
}
