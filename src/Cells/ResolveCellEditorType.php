<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Cells;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Waguilar\FilamentDataGrid\Exceptions\UnsupportedCellComponent;

final class ResolveCellEditorType
{
    public function handle(Component $component): CellEditorType
    {
        return match (true) {
            $component instanceof TextInput => $component->isNumeric() ? CellEditorType::Number : CellEditorType::Text,
            $component instanceof Select, $component instanceof Radio, $component instanceof ToggleButtons => CellEditorType::Select,
            $component instanceof Toggle, $component instanceof Checkbox => CellEditorType::Toggle,
            $component instanceof DateTimePicker => CellEditorType::Date,
            $component instanceof Entry => CellEditorType::Entry,
            $component instanceof Field => CellEditorType::Modal,
            default => throw UnsupportedCellComponent::for($component),
        };
    }
}
