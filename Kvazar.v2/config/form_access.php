<?php
return [
    'admin' => [
        'all' => true, // Admin has access to everything
    ],
    'ceo' => [
        'all' => true, // CEO has access to everything
    ],
    'operator' => [
        'edit' => [
            'client',
            'order_number',
            'order_date',
            'shipping_type',
            'transport_type',
            'cargo_weight',
            'cargo_volume',
            'cargo_quantity',
            'min_temperature',
            'max_temperature',
            'temp_print_list',
            'cargo_name',
            'loading_type',
            'packaging_type',
            'cargo_price',
            'currency_id',
            'insurance_status',
            'transport_hours',
            'overwork_hours',
            'order_notes'
        ],
        'view' => ['*'] // Can view all fields
    ],
    'client' => [
        'edit' => [
            'order_date',
            'cargo_weight',
            'cargo_volume',
            'cargo_quantity',
            'min_temperature',
            'max_temperature',
            'temp_print_list',
            'cargo_name',
            'loading_type',
            'packaging_type',
            'order_notes'
        ],
        'view' => ['*'] // Can view all fields
    ]
]; 