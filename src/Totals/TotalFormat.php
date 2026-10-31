<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Totals;

use NumberFormatter;

final readonly class TotalFormat
{
    private const GROUPING_PROBE = 1234;

    /**
     * @return array<string, int|string|bool>
     */
    public static function plain(?int $decimalPlaces, string $decimalSeparator, string $thousandsSeparator): array
    {
        return [
            'minFraction' => $decimalPlaces ?? 0,
            'maxFraction' => $decimalPlaces ?? 0,
            'decimal' => $decimalSeparator,
            'group' => $thousandsSeparator,
            'groupSize' => 3,
            'secondaryGroupSize' => 3,
            'minGrouping' => 1,
            'positivePrefix' => '',
            'positiveSuffix' => '',
            'negativePrefix' => '-',
            'negativeSuffix' => '',
            'halfEven' => false,
        ];
    }

    /**
     * @return array<string, int|string|bool>
     */
    public static function number(string $locale, ?int $decimalPlaces, ?int $maxDecimalPlaces): array
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);

        if ($maxDecimalPlaces !== null) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimalPlaces);
        } elseif ($decimalPlaces !== null) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimalPlaces);
        }

        return self::fromFormatter(
            $formatter,
            fn (float $value): string => (string) $formatter->format($value),
            (int) $formatter->getAttribute(NumberFormatter::MIN_FRACTION_DIGITS),
            (int) $formatter->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS),
            NumberFormatter::DECIMAL_SEPARATOR_SYMBOL,
            NumberFormatter::GROUPING_SEPARATOR_SYMBOL,
        );
    }

    /**
     * @return array<string, int|string|bool>
     */
    public static function money(string $locale, string $currency, ?int $decimalPlaces): array
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        if ($decimalPlaces !== null) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimalPlaces);
        }

        $format = fn (float $value): string => (string) $formatter->formatCurrency($value, $currency);
        $decimal = $formatter->getSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL);
        $fraction = $decimal === '' ? '' : (explode($decimal, self::core($format(1.0)))[1] ?? '');

        return self::fromFormatter(
            $formatter,
            $format,
            strlen($fraction),
            strlen($fraction),
            NumberFormatter::MONETARY_SEPARATOR_SYMBOL,
            NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL,
        );
    }

    /**
     * @param  callable(float): string  $format
     * @return array<string, int|string|bool>
     */
    private static function fromFormatter(NumberFormatter $formatter, callable $format, int $minFraction, int $maxFraction, int $decimalSymbol, int $groupSymbol): array
    {
        $group = $formatter->getSymbol($groupSymbol);
        $groupSize = (int) $formatter->getAttribute(NumberFormatter::GROUPING_SIZE);
        $secondaryGroupSize = (int) $formatter->getAttribute(NumberFormatter::SECONDARY_GROUPING_SIZE);
        [$positivePrefix, $positiveSuffix] = self::affixes($format(1.0));
        [$negativePrefix, $negativeSuffix] = self::affixes($format(-1.0));

        return [
            'minFraction' => $minFraction,
            'maxFraction' => $maxFraction,
            'decimal' => $formatter->getSymbol($decimalSymbol),
            'group' => $group,
            'groupSize' => $groupSize,
            'secondaryGroupSize' => $secondaryGroupSize > 0 ? $secondaryGroupSize : $groupSize,
            'minGrouping' => $groupSize > 0 && str_contains(self::core($format(self::GROUPING_PROBE)), $group) ? 1 : 2,
            'positivePrefix' => $positivePrefix,
            'positiveSuffix' => $positiveSuffix,
            'negativePrefix' => $negativePrefix,
            'negativeSuffix' => $negativeSuffix,
            'halfEven' => true,
        ];
    }

    /**
     * @return array{string, string}
     */
    private static function affixes(string $formatted): array
    {
        preg_match('/^(\D*)\d.*?(\D*)$/su', $formatted, $matches);

        return [$matches[1] ?? '', $matches[2] ?? ''];
    }

    private static function core(string $formatted): string
    {
        preg_match('/\d.*\d|\d/su', $formatted, $matches);

        return $matches[0] ?? '';
    }
}
