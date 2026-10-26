<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Waguilar33\FilamentDataGrid\Exceptions\UnsupportedCellComponent;

final class ResolveCellEditorType
{
    public function handle(Component $component): CellEditorType
    {
        return match (true) {
            $component instanceof TextInput => $component->isNumeric() ? CellEditorType::Number : CellEditorType::Text,
            $component instanceof Select => $this->resolveSelect($component),
            $component instanceof Toggle, $component instanceof Checkbox => CellEditorType::Toggle,
            $component instanceof Entry => CellEditorType::Entry,
            default => throw UnsupportedCellComponent::for($component),
        };
    }

    private function resolveSelect(Select $select): CellEditorType
    {
        if ($select->isMultiple()) {
            throw UnsupportedCellComponent::for($select);
        }

        return $select->isSearchable() && ($select->hasDynamicSearchResults() || $select->hasRelationship())
            ? CellEditorType::Search
            : CellEditorType::Select;
    }
}
