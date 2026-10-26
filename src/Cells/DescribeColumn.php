<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;

final readonly class DescribeColumn
{
    public function __construct(private ResolveCellEditorType $resolveEditorType) {}

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
            ],
            $component instanceof Select => [
                'placeholder' => $component->getPlaceholder(),
                'searchable' => $component->isSearchable(),
                'searchDebounce' => $component->getSearchDebounce(),
                'searchPrompt' => $this->text($component->getSearchPrompt()),
                'noResultsMessage' => $this->text($component->getNoSearchResultsMessage()),
                'searchingMessage' => $this->text($component->getSearchingMessage()),
            ],
            default => [],
        };
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
