<?php

declare(strict_types=1);

return [
    'actions' => [
        'edit_cell' => [
            'label' => 'Edit :label',
        ],
    ],
    'columns' => [
        'reorder' => 'Reorder',
        'actions' => 'Actions',
    ],
    'entry' => [
        'empty' => 'No results.',
        'expand' => 'Expand row',
        'collapse' => 'Collapse row',
        'open_record' => 'Open',
        'no_children' => 'No lines.',
    ],
    'errors' => [
        'row' => 'Row :row',
        'child_row' => ':label row :row',
    ],
    'validation' => [
        'date' => 'The :attribute field must be a valid date.',
        'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
        'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
        'numeric' => 'The :attribute field must be a number.',
        'integer' => 'The :attribute field must be an integer.',
        'min' => 'The :attribute field must be at least :min.',
        'max' => 'The :attribute field must not be greater than :max.',
    ],
    'step' => [
        'up' => 'Increase',
        'down' => 'Decrease',
    ],
    'toggle' => [
        'on' => 'On',
        'off' => 'Off',
    ],
];
