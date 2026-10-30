<?php

declare(strict_types=1);

return [
    'actions' => [
        'edit_cell' => [
            'label' => 'Editar :label',
        ],
        'row_menu' => [
            'label' => 'Acciones de la fila',
        ],
    ],
    'columns' => [
        'reorder' => 'Reordenar',
        'actions' => 'Acciones',
    ],
    'entry' => [
        'empty' => 'Sin resultados.',
        'expand' => 'Expandir fila',
        'collapse' => 'Contraer fila',
        'open_record' => 'Abrir',
        'no_children' => 'Sin líneas.',
    ],
    'errors' => [
        'row' => 'Fila :row',
        'child_row' => ':label fila :row',
    ],
    'validation' => [
        'date' => 'El campo :attribute debe ser una fecha válida.',
        'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
        'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
        'numeric' => 'El campo :attribute debe ser un número.',
        'integer' => 'El campo :attribute debe ser un número entero.',
        'min' => 'El campo :attribute debe ser al menos :min.',
        'max' => 'El campo :attribute no debe ser mayor que :max.',
    ],
    'toggle' => [
        'on' => 'Sí',
        'off' => 'No',
    ],
];
