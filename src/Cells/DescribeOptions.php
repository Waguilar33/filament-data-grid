<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Contracts\Support\Htmlable;

final class DescribeOptions
{
    /**
     * @return list<array{label: string, value: string, isDisabled: bool}>
     */
    public function handle(Radio|ToggleButtons $component): array
    {
        $options = [];

        foreach ($component->getOptions() as $value => $label) {
            if (is_array($label)) {
                continue;
            }

            $options[] = [
                'label' => $this->text($label),
                'value' => (string) $value,
                'isDisabled' => $component->isOptionDisabled($value, $label),
            ];
        }

        return $options;
    }

    public function label(Select|Radio|ToggleButtons $component): ?string
    {
        $state = $component->getState();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if (blank($state) || ! is_scalar($state)) {
            return null;
        }

        if ($component instanceof Select) {
            return $component->getOptionLabel(withDefault: false);
        }

        $label = $component->getOptions()[(string) $state] ?? null;

        return is_string($label) ? $label : null;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function labels(Select|ToggleButtons $component): array
    {
        $state = $component->getState();

        if (! is_array($state) || $state === []) {
            return [];
        }

        if ($component instanceof Select) {
            $labels = $component->getOptionLabels();
        } else {
            $selected = array_map(strval(...), array_filter($state, is_scalar(...)));
            $labels = array_intersect_key($component->getOptions(), array_flip($selected));
        }

        $result = [];

        foreach ($labels as $value => $label) {
            if (is_string($label)) {
                $result[] = ['label' => $label, 'value' => (string) $value];
            }
        }

        return $result;
    }

    private function text(string|Htmlable $label): string
    {
        return $label instanceof Htmlable ? strip_tags($label->toHtml()) : $label;
    }
}
