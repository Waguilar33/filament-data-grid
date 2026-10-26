<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Illuminate\Support\MessageBag;

final readonly class DescribeCell
{
    public function __construct(private ResolveCellEditorType $resolveEditorType) {}

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
            $component instanceof Select && $type === CellEditorType::Select => [
                'options' => $component->getOptionsForJs(),
                'label' => $this->optionLabel($component),
            ],
            $component instanceof Select => ['label' => $this->optionLabel($component)],
            default => [],
        };
    }

    private function optionLabel(Select $select): ?string
    {
        return blank($select->getState()) ? null : $select->getOptionLabel(withDefault: false);
    }

    private function entryHtml(Entry $entry): string
    {
        $entry->hiddenLabel();

        return $entry instanceof HasEmbeddedView ? $entry->toEmbeddedHtml() : $entry->toHtml();
    }
}
