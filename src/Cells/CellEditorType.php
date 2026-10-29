<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Cells;

enum CellEditorType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case Toggle = 'toggle';
    case Date = 'date';
    case Entry = 'entry';
    case Modal = 'modal';
}
