<?php

declare(strict_types=1);

return [
    'generation' => [
        'default_strategy' => 'random',

        'strategies' => [
            'random' => [
                'length' => 12,
            ],
            'timestamp' => [
                'prefix' => '',
            ],
            'prefix' => [
                'prefix' => 'BC-',
                'length' => 8,
            ],
            'sequential' => [
                'prefix' => 'BC-',
                'counter_key' => 'barcode.value_generator.sequential',
                'pad_length' => 8,
            ],
        ],
    ],
];
