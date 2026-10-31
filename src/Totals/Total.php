<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Totals;

use BackedEnum;
use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Enums\ArgumentValue;
use Filament\Support\RawJs;

final class Total
{
    use EvaluatesClosures;

    private ?Closure $describeFormat = null;

    private string|Closure|null $prefix = null;

    private string|Closure|null $suffix = null;

    private RawJs|Closure|null $formatUsing = null;

    public function __construct(private readonly string $field) {}

    public static function make(string $field): self
    {
        return new self($field);
    }

    public function numeric(int|Closure|null $decimalPlaces = null, string|Closure|ArgumentValue $decimalSeparator = ArgumentValue::Default, string|Closure|ArgumentValue $thousandsSeparator = ArgumentValue::Default, int|Closure|null $maxDecimalPlaces = null, string|BackedEnum|Closure|null $locale = null): self
    {
        $this->describeFormat = function () use ($decimalPlaces, $decimalSeparator, $thousandsSeparator, $maxDecimalPlaces, $locale): array {
            $decimalPlaces = $this->integerOrNull($decimalPlaces);
            $decimalSeparator = $this->evaluate($decimalSeparator);
            $thousandsSeparator = $this->evaluate($thousandsSeparator);

            if ($decimalSeparator !== ArgumentValue::Default || $thousandsSeparator !== ArgumentValue::Default) {
                return TotalFormat::plain(
                    $decimalPlaces,
                    is_string($decimalSeparator) ? $decimalSeparator : '.',
                    is_string($thousandsSeparator) ? $thousandsSeparator : ',',
                );
            }

            return TotalFormat::number($this->locale($locale), $decimalPlaces, $this->integerOrNull($maxDecimalPlaces));
        };

        return $this;
    }

    public function money(string|BackedEnum|Closure $currency, string|BackedEnum|Closure|null $locale = null, int|Closure|null $decimalPlaces = null): self
    {
        $this->describeFormat = fn (): array => TotalFormat::money(
            $this->locale($locale),
            $this->stringOrNull($currency) ?? 'USD',
            $this->integerOrNull($decimalPlaces),
        );

        return $this;
    }

    public function prefix(string|Closure|null $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string|Closure|null $suffix): self
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function formatUsing(RawJs|Closure|null $formatter): self
    {
        $this->formatUsing = $formatter;

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return array{number: array<string, int|string|bool>, prefix: string, suffix: string, js: string|null}
     */
    public function describe(): array
    {
        $formatter = $this->evaluate($this->formatUsing);

        /** @var array<string, int|string|bool> $number */
        $number = $this->describeFormat === null ? TotalFormat::number($this->locale(null), null, null) : ($this->describeFormat)();

        return [
            'number' => $number,
            'prefix' => $this->stringOrNull($this->prefix) ?? '',
            'suffix' => $this->stringOrNull($this->suffix) ?? '',
            'js' => $formatter instanceof RawJs ? $formatter->toHtml() : null,
        ];
    }

    private function locale(string|BackedEnum|Closure|null $locale): string
    {
        return $this->stringOrNull($locale) ?? app()->getLocale();
    }

    private function integerOrNull(int|Closure|null $value): ?int
    {
        $value = $this->evaluate($value);

        return is_int($value) ? $value : null;
    }

    private function stringOrNull(string|BackedEnum|Closure|null $value): ?string
    {
        $value = $this->evaluate($value);

        return match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            is_string($value) => $value,
            default => null,
        };
    }
}
