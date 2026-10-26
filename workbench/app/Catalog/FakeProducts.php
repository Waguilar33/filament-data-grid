<?php

declare(strict_types=1);

namespace Workbench\App\Catalog;

final class FakeProducts
{
    private const array ADJECTIVES = ['Fresh', 'Organic', 'Roasted', 'Smoked', 'Spicy', 'Sweet', 'Classic', 'Premium', 'Light', 'Golden', 'Wild', 'Crispy', 'Creamy', 'Tropical', 'Rustic'];

    private const array NOUNS = ['Coffee', 'Tea', 'Cheese', 'Bread', 'Honey', 'Salsa', 'Chocolate', 'Granola', 'Jam', 'Tortilla', 'Butter', 'Olive Oil', 'Pasta', 'Rice', 'Beans', 'Yogurt', 'Juice', 'Cookies', 'Nuts', 'Vinegar'];

    private const array SIZES = ['100 g', '250 g', '500 g', '1 kg', '330 ml', '1 l', '6-pack', '12-pack'];

    /**
     * @var list<array{id: int, name: string, sku: string, price: float}>|null
     */
    private static ?array $products = null;

    /**
     * @return list<array{id: int, name: string, sku: string, price: float}>
     */
    public static function all(int $count = 5000): array
    {
        return self::$products ??= self::generate($count);
    }

    /**
     * @return list<array{id: int, name: string, sku: string, price: float}>
     */
    private static function generate(int $count): array
    {
        $products = [];

        for ($id = 1; $id <= $count; $id++) {
            $products[] = [
                'id' => $id,
                'name' => sprintf(
                    '%s %s %s',
                    self::ADJECTIVES[$id % count(self::ADJECTIVES)],
                    self::NOUNS[intdiv($id, count(self::ADJECTIVES)) % count(self::NOUNS)],
                    self::SIZES[intdiv($id, count(self::ADJECTIVES) * count(self::NOUNS)) % count(self::SIZES)],
                ),
                'sku' => sprintf('SKU-%05d', $id),
                'price' => round((($id % 40) + 3) * 1.25, 2),
            ];
        }

        return $products;
    }

    /**
     * @return array{id: int, name: string, sku: string, price: float}|null
     */
    public static function find(mixed $id): ?array
    {
        return is_numeric($id) ? (self::all()[(int) $id - 1] ?? null) : null;
    }

    /**
     * @return array<int, string>
     */
    public static function search(string $search, int $limit = 20): array
    {
        $needle = mb_strtolower(trim($search));
        $results = [];

        foreach (self::all() as $product) {
            if (mb_strtolower($product['sku']) === $needle || str_contains(mb_strtolower($product['name']), $needle)) {
                $results[$product['id']] = sprintf(
                    '<div>%s</div><div style="font-size: 0.75rem; opacity: 0.65">%s</div>',
                    e($product['name']),
                    e($product['sku']),
                );
            }

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
