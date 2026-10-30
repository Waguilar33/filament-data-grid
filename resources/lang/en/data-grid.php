<?php

declare(strict_types=1);

return [
    'actions' => [
        'edit_cell' => [
            'label' => 'Edit :label',
        ],
        'row_menu' => [
            'label' => 'Row actions',
        ],
    ],
    'columns' => [
        'reorder' => 'Reorder',
        'actions' => 'Actions',
    ],
    'entry' => [
        'search' => 'Search',
        'empty' => 'No results.',
        'expand' => 'Expand row',
        'collapse' => 'Collapse row',
        'no_children' => 'No lines.',
        'pagination' => [
            'label' => 'Pagination',
            'overview' => 'Showing :first to :last of :total',
            'previous' => 'Previous page',
            'next' => 'Next page',
        ],
    ],
    'errors' => [
        'row' => 'Row :row',
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
    'toggle' => [
        'on' => 'On',
        'off' => 'Off',
    ],
];
