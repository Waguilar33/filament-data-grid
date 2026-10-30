<?php

declare(strict_types=1);

return [
    'dispatch' => [
        'navigation' => 'Dispatch',
        'section' => 'Products to dispatch',
        'description' => 'Open a product to choose how many units leave each place, or to enter the code of each unit.',
        'columns' => [
            'product' => 'Product',
            'requested' => 'Requested',
            'assigned' => 'Assigned',
            'status' => 'Status',
            'place' => 'Place',
            'available' => 'Available',
            'quantity' => 'Quantity',
            'code' => 'Unit code',
        ],
        'statuses' => [
            'complete' => 'Complete',
            'partial' => 'Partial',
            'empty' => 'Not assigned',
        ],
        'saved' => 'Dispatch saved',
    ],
    'route' => [
        'table_navigation' => 'Delivery table',
        'descriptions' => [
            'browser' => 'Deliveries on this route, filtered in the browser.',
            'server' => 'The same deliveries, loaded page by page from the server.',
        ],
        'sections' => [
            'browser' => 'Rows sent to the browser',
            'server' => 'Rows loaded from the server',
        ],
        'navigation' => 'Delivery route',
        'columns' => [
            'kind' => 'Type',
            'preparation' => 'Preparation',
            'delivery' => 'Delivery',
            'customer' => 'Customer',
            'warehouse' => 'Warehouse',
            'product' => 'Product',
            'requested' => 'Requested',
            'handed_over' => 'Handed over',
        ],
        'details' => [
            'address' => 'Address',
            'phone' => 'Phone',
            'route' => 'Route',
            'notes' => 'Notes',
        ],
        'kinds' => [
            'shipment' => 'Shipment',
            'pickup' => 'Pick-up',
        ],
        'holds' => [
            'ready' => 'Ready',
            'awaiting_stock' => 'Awaiting stock',
            'awaiting_payment' => 'Awaiting payment',
        ],
        'actions' => [
            'edit' => 'Edit',
            'edited' => 'Delivery :number edited',
            'deliver' => 'Deliver',
            'delivered' => 'Delivery :number marked as delivered',
            'reschedule' => 'Reschedule',
            'date' => 'New date',
            'rescheduled' => 'Delivery :number rescheduled',
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
