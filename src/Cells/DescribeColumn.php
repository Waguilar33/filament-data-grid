<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Cells;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;

final readonly class DescribeColumn
{
    public function __construct(
        private ResolveCellEditorType $resolveEditorType,
        private DescribeDate $describeDate,
        private DescribeSelect $describeSelect,
    ) {}

    /**
     * @return array{field: string, label: string, alignment: string|null, width: string|null, required: bool, headerHidden: bool, editor: array<string, mixed>}
     */
    public function handle(Component $component, ?TableColumn $column): array
    {
        $type = $this->resolveEditorType->handle($component);
        $alignment = $column?->getAlignment();

        return [
            'field' => $this->fieldName($component),
            'label' => $this->text($column?->getLabel() ?? $this->componentLabel($component)),
            'alignment' => $alignment instanceof Alignment ? $alignment->value : $alignment,
            'width' => $column?->getWidth(),
            'required' => $column?->isMarkedAsRequired() ?? ($component instanceof Field && $component->isRequired()),
            'headerHidden' => $column?->isHeaderLabelHidden() ?? false,
            'editor' => [
                'type' => $type->value,
                ...$this->editorOptions($component, $type),
            ],
        ];
    }

    public function fieldName(Component $component): string
    {
        return match (true) {
            $component instanceof Field => $component->getName(),
            $component instanceof Entry => $component->getName(),
            default => (string) $component->getKey(isAbsolute: false),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function editorOptions(Component $component, CellEditorType $type): array
    {
        return match (true) {
            $component instanceof TextInput => [
                'placeholder' => $component->getPlaceholder(),
                'integer' => $component->isInteger(),
                ...($type === CellEditorType::Number ? ['messages' => $this->numberMessages()] : []),
            ],
            $component instanceof Select, $component instanceof Radio, $component instanceof ToggleButtons => [
                'select' => $this->describeSelect->column($component),
            ],
            $component instanceof DateTimePicker => [
                'placeholder' => $component->getPlaceholder(),
                'inputType' => $this->describeDate->inputType($component),
                'internalFormat' => $component->getInternalFormat(),
                'messages' => $this->dateMessages(),
            ],
            $component instanceof Field && $type === CellEditorType::Modal => [
                'editLabel' => $this->text(__('filament-data-grid::data-grid.actions.edit_cell.label', ['label' => $this->text($component->getLabel())])),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function numberMessages(): array
    {
        return [
            'numeric' => $this->text(__('filament-data-grid::data-grid.validation.numeric')),
            'integer' => $this->text(__('filament-data-grid::data-grid.validation.integer')),
            'min' => $this->text(__('filament-data-grid::data-grid.validation.min')),
            'max' => $this->text(__('filament-data-grid::data-grid.validation.max')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dateMessages(): array
    {
        return [
            'date' => $this->text(__('filament-data-grid::data-grid.validation.date')),
            'after_or_equal' => $this->text(__('filament-data-grid::data-grid.validation.after_or_equal')),
            'before_or_equal' => $this->text(__('filament-data-grid::data-grid.validation.before_or_equal')),
        ];
    }

    private function componentLabel(Component $component): string|Htmlable|null
    {
        return match (true) {
            $component instanceof Field => $component->getLabel(),
            $component instanceof Entry => $component->getLabel(),
            default => null,
        };
    }

    private function text(string|Htmlable|null $label): string
    {
        return $label instanceof Htmlable ? strip_tags($label->toHtml()) : (string) $label;
    }
}
