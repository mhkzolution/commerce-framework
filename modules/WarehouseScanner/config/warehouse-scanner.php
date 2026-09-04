<?php

declare(strict_types=1);

return [
    'low_stock_threshold' => (int) env('WAREHOUSE_LOW_STOCK_THRESHOLD', 5),

    'history_limit' => 10,

    'modes' => [
        'stock-check' => [
            'label' => 'Stock Check',
            'shortcut' => 'F1',
            'permission' => 'warehouse.scan',
        ],
        'label-attachment' => [
            'label' => 'Label Attachment',
            'shortcut' => 'F2',
            'permission' => 'warehouse.scan',
        ],
        'receiving' => [
            'label' => 'Receiving',
            'shortcut' => 'F3',
            'permission' => 'warehouse.receive',
        ],
        'picking' => [
            'label' => 'Picking',
            'shortcut' => 'F4',
            'permission' => 'warehouse.scan',
        ],
        'packing' => [
            'label' => 'Packing',
            'shortcut' => 'F5',
            'permission' => 'warehouse.scan',
        ],
        'transfer' => [
            'label' => 'Transfer',
            'shortcut' => 'F6',
            'permission' => 'warehouse.transfer',
        ],
        'inventory-count' => [
            'label' => 'Inventory Count',
            'shortcut' => 'F7',
            'permission' => 'warehouse.count',
        ],
    ],

    'mock_pick_order' => [
        'order_number' => 'ORD-1042',
        'lines' => [
            ['sku' => 'MOCK-HOOD-BLK-M', 'name' => 'Classic Hoodie — Black / M', 'quantity' => 2],
            ['sku' => 'MOCK-TEE-001', 'name' => 'Essential Tee', 'quantity' => 1],
        ],
    ],
];
