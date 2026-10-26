<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Cells;

use BackedEnum;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

final class DescribeStateText
{
    public function handle(Field $field): ?string
    {
        $state = $field->getState();
        $values = is_array($state) ? $state : [$state];
        $options = $field instanceof Select || $field instanceof ToggleButtons ? $field->getOptions() : [];
        $texts = [];

        foreach ($values as $value) {
            $text = $this->text($value, $options);

            if (filled($text)) {
                $texts[] = $text;
            }
        }

        return $texts === [] ? null : implode(', ', $texts);
    }

    /**
     * @param  array<array-key, mixed>  $options
     */
    private function text(mixed $value, array $options): ?string
    {
        if ($value instanceof HasLabel) {
            $label = $value->getLabel();

            return $label instanceof Htmlable ? strip_tags($label->toHtml()) : $label;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $label = $options[(string) $value] ?? null;

        return match (true) {
            is_string($label) => $label,
            is_array($label) => null,
            is_bool($value) => $value ? $this->onLabel() : null,
            default => (string) $value,
        };
    }

    private function onLabel(): ?string
    {
        $label = __('filament-data-grid::data-grid.toggle.on');

        return is_string($label) ? $label : null;
    }
}
