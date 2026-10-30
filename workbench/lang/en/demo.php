<?php

declare(strict_types=1);

return [
    'route' => [
        'navigation' => 'Delivery route',
        'columns' => [
            'delivery' => 'Delivery',
            'customer' => 'Customer',
            'warehouse' => 'Warehouse',
            'product' => 'Product',
            'code' => 'Unit code',
            'requested' => 'Requested',
            'handed_over' => 'Handed over',
        ],
        'fill_statuses' => [
            'complete' => 'Complete',
            'partial' => 'Partial',
        ],
    ],
    'navigation' => 'Data grid',
    'sections' => [
        'lines' => 'Products',
        'saved_state' => 'Last saved state',
        'adjustments' => 'Adjustments',
    ],
    'actions' => [
        'save' => 'Save',
        'edit_in_slide_over' => 'Edit in a slide-over',
        'add_sample_lines' => 'Add sample lines',
        'stock' => 'Stock',
        'salesperson' => 'Salesperson',
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
        'kind' => 'Type',
        'status' => 'Status',
        'date' => 'Date',
        'color' => 'Color',
        'available' => 'Available',
        'salesperson' => 'Salesperson',
        'approved' => 'Approved',
    ],
    'kinds' => [
        'credit' => 'Credit',
        'debit' => 'Debit',
    ],
    'statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
    ],
    'units' => [
        'box' => 'Box',
        'kg' => 'Kilogram',
        'unit' => 'Unit',
    ],
    'warehouse_option' => '{0} :zone > :place · none available|{1} :zone > :place · 1 available|[2,*] :zone > :place · :count available',
    'saved_lines' => '{0} Saved with no lines|{1} Saved 1 line|[2,*] Saved :count lines',
];
