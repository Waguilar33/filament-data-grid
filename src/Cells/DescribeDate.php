<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Cells;

use Carbon\CarbonInterface;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Carbon;
use Throwable;

final class DescribeDate
{
    public function inputType(DateTimePicker $picker): string
    {
        return match (true) {
            ! $picker->hasDate() => 'time',
            $picker->hasTime() => 'datetime-local',
            default => 'date',
        };
    }

    /**
     * @return array{min: string|null, max: string|null, step: int|null, label: string|null}
     */
    public function handle(DateTimePicker $picker): array
    {
        return [
            'min' => $this->forInput($picker, $picker->getMinDate()),
            'max' => $this->forInput($picker, $picker->getMaxDate()),
            'step' => $picker->hasTime() && $picker->hasSeconds() ? 1 : null,
            'label' => $this->label($picker),
        ];
    }

    private function label(DateTimePicker $picker): ?string
    {
        $date = $this->parse($picker->getRawState());

        return $date?->translatedFormat($picker->getDisplayFormat());
    }

    private function forInput(DateTimePicker $picker, ?string $value): ?string
    {
        $date = $this->parse($value);

        if ($date === null) {
            return null;
        }

        return $date->format(match ($this->inputType($picker)) {
            'time' => $picker->hasSeconds() ? 'H:i:s' : 'H:i',
            'datetime-local' => $picker->hasSeconds() ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i',
            default => 'Y-m-d',
        });
    }

    private function parse(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (! is_string($value) || blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
