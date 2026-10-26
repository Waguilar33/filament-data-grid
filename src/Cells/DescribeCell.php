<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Illuminate\Support\MessageBag;

final readonly class DescribeCell
{
    public function __construct(
        private ResolveCellEditorType $resolveEditorType,
        private DescribeSelect $describeSelect,
        private DescribeDate $describeDate,
        private DescribeStateText $describeStateText,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Component $component, MessageBag $errors): array
    {
        if ($component->isHidden()) {
            return ['visible' => false];
        }

        $type = $this->resolveEditorType->handle($component);

        if ($component instanceof Entry) {
            return ['visible' => true, 'html' => $this->entryHtml($component)];
        }

        $statePath = $component->getStatePath();

        return array_filter([
            'visible' => true,
            'key' => $component->getKey(),
            'disabled' => $component->isDisabled(),
            'required' => $component instanceof Field && $component->isRequired(),
            'live' => $component->isLive(),
            'error' => $errors->first($statePath) ?: null,
            ...$this->editorState($component, $type),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function editorState(Component $component, CellEditorType $type): array
    {
        return match (true) {
            $component instanceof TextInput && $type === CellEditorType::Number => [
                'min' => $component->getMinValue(),
                'max' => $component->getMaxValue(),
                'step' => $component->getStep(),
            ],
            $component instanceof Select || $component instanceof Radio || $component instanceof ToggleButtons => $this->describeSelect->cell($component),
            $component instanceof DateTimePicker => $this->describeDate->handle($component),
            $component instanceof Field && $type === CellEditorType::Modal => [
                'label' => $this->describeStateText->handle($component),
            ],
            default => [],
        };
    }

    private function entryHtml(Entry $entry): string
    {
        $entry->hiddenLabel();

        return $entry instanceof HasEmbeddedView ? $entry->toEmbeddedHtml() : $entry->toHtml();
    }
}
