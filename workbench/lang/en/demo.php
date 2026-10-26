<?php

declare(strict_types=1);

return [
    'navigation' => 'Data grid',
    'sections' => [
        'lines' => 'Products',
        'adjustments' => 'Adjustments',
    ],
    'actions' => [
        'save' => 'Save',
        'edit_in_slide_over' => 'Edit in a slide-over',
        'add_sample_lines' => 'Add sample lines',
        'add_adjustment' => 'Add adjustment',
    ],
    'columns' => [
        'product' => 'Product',
        'quantity' => 'Quantity',
        'unit' => 'Unit',
        'warehouse' => 'Warehouse',
        'gift' => 'Gift',
        'comment' => 'Comment',
        'total' => 'Total',
        'reason' => 'Reason',
        'amount' => 'Amount',
        'approved' => 'Approved',
    ],
    'units' => [
        'box' => 'Box',
        'kg' => 'Kilogram',
        'unit' => 'Unit',
    ],
    'warehouse_option' => '{0} :zone > :place · none available|{1} :zone > :place · 1 available|[2,*] :zone > :place · :count available',
    'saved_lines' => '{0} Saved with no lines|{1} Saved 1 line|[2,*] Saved :count lines',
];
