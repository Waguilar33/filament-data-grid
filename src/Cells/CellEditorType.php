<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

enum CellEditorType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case Search = 'search';
    case Toggle = 'toggle';
    case Entry = 'entry';
}
