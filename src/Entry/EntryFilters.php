<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Contracts\Support\Htmlable;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedFilter;

final readonly class EntryFilters
{
    /**
     * @param  list<BaseFilter>  $filters
     */
    public function __construct(private array $filters) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function describe(): array
    {
        return array_map(fn (BaseFilter $filter): array => [
            'name' => $filter->getName(),
            'label' => $this->text($filter->getLabel()),
            ...match (true) {
                $filter instanceof TernaryFilter => [
                    'type' => 'ternary',
                    'placeholder' => $this->text($filter->getPlaceholder()),
                    'options' => [
                        ['value' => 'true', 'label' => $this->text($filter->getTrueLabel())],
                        ['value' => 'false', 'label' => $this->text($filter->getFalseLabel())],
                    ],
                ],
                $filter instanceof SelectFilter => [
                    'type' => 'select',
                    'multiple' => $filter->isMultiple(),
                    'placeholder' => $this->text($filter->getPlaceholder() ?? __('filament-forms::components.select.placeholder')),
                    'options' => $this->options($filter),
                ],
                default => ['type' => 'checkbox'],
            },
        ], $this->filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $state = [];

        foreach ($this->filters as $filter) {
            $value = $input[$filter->getName()] ?? null;

            $state[$filter->getName()] = match (true) {
                $filter instanceof TernaryFilter => match ($value) {
                    true, 'true', '1', 1 => true,
                    false, 'false', '0', 0 => false,
                    default => null,
                },
                $filter instanceof SelectFilter && $filter->isMultiple() => array_values(array_intersect(
                    array_map(strval(...), array_filter(is_array($value) ? $value : [], is_scalar(...))),
                    array_map(strval(...), array_keys($this->flatOptions($filter))),
                )),
                $filter instanceof SelectFilter => is_scalar($value) && array_key_exists((string) $value, $this->flatOptions($filter)) ? (string) $value : null,
                default => $value === true || $value === 'true' || $value === '1',
            };
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, array<string, mixed>>
     */
    public function toFilamentData(array $state): array
    {
        $data = [];

        foreach ($this->filters as $filter) {
            $value = $state[$filter->getName()] ?? null;

            $data[$filter->getName()] = match (true) {
                $filter instanceof TernaryFilter => ['value' => $value],
                $filter instanceof SelectFilter && $filter->isMultiple() => ['values' => is_array($value) ? $value : []],
                $filter instanceof SelectFilter => ['value' => $value],
                default => ['isActive' => $value === true],
            };
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function matches(Schema $item, array $state): bool
    {
        foreach ($this->filters as $filter) {
            $active = $state[$filter->getName()] ?? null;

            if ($active === null || $active === [] || $active === false && $filter instanceof Filter) {
                continue;
            }

            $value = $this->rawValue($item, $filter);

            $isMatch = match (true) {
                $filter instanceof TernaryFilter => (bool) $value === $active,
                $filter instanceof SelectFilter && is_array($active) => in_array($this->scalar($value), $active, true),
                $filter instanceof SelectFilter => $this->scalar($value) === $active,
                default => throw UnsupportedFilter::withoutRecords($filter),
            };

            if (! $isMatch) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string|bool|null>
     */
    public function rawValues(Schema $item): array
    {
        $values = [];

        foreach ($this->filters as $filter) {
            $name = (string) $filter->getName();

            if ($filter instanceof TernaryFilter) {
                $values[$name] = (bool) $this->rawValue($item, $filter);
            } elseif ($filter instanceof SelectFilter) {
                $values[$name] = $this->scalar($this->rawValue($item, $filter));
            }
        }

        return $values;
    }

    public function assertSupportedWithoutRecords(): void
    {
        foreach ($this->filters as $filter) {
            if (! $filter instanceof SelectFilter) {
                throw UnsupportedFilter::withoutRecords($filter);
            }
        }
    }

    private function rawValue(Schema $item, BaseFilter $filter): mixed
    {
        $attribute = $filter instanceof SelectFilter ? $filter->getAttribute() : (string) $filter->getName();
        $source = $item->getRecord(withParentComponentRecord: false) ?? $item->getConstantState();

        return data_get($source, $attribute);
    }

    private function scalar(mixed $value): ?string
    {
        return match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default => null,
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function options(SelectFilter $filter): array
    {
        $options = [];

        foreach ($this->flatOptions($filter) as $value => $label) {
            $options[] = ['value' => (string) $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function flatOptions(SelectFilter $filter): array
    {
        $options = [];

        foreach ($filter->getOptions() as $value => $label) {
            if (is_array($label)) {
                foreach ($label as $groupValue => $groupLabel) {
                    $options[(string) $groupValue] = $this->text($groupLabel);
                }
            } else {
                $options[(string) $value] = $this->text($label);
            }
        }

        return $options;
    }

    private function text(mixed $value): string
    {
        return match (true) {
            $value instanceof Htmlable => strip_tags($value->toHtml()),
            is_scalar($value) => (string) $value,
            default => '',
        };
    }
}
