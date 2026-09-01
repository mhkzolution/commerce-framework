<?php

declare(strict_types=1);

return [
    'paper_sizes' => [
        'a4' => [
            'label' => 'A4',
            'width_mm' => 210,
            'height_mm' => 297,
        ],
        'a5' => [
            'label' => 'A5',
            'width_mm' => 148,
            'height_mm' => 210,
        ],
        'thermal' => [
            'label' => 'Thermal Label',
            'width_mm' => 50,
            'height_mm' => 30,
        ],
        'custom' => [
            'label' => 'Custom',
            'width_mm' => 210,
            'height_mm' => 297,
        ],
    ],

    'default_template' => [
        'name' => 'A4 4×10',
        'paper_size' => 'a4',
        'rows' => 10,
        'columns' => 4,
        'margin_top' => 10,
        'margin_right' => 10,
        'margin_bottom' => 10,
        'margin_left' => 10,
        'spacing_horizontal' => 2,
        'spacing_vertical' => 2,
        'label_width' => 48.5,
        'label_height' => 25.4,
        'label_orientation' => 'vertical',
    ],

    'label_style' => [
        'label_padding_top' => 1,
        'label_padding_right' => 2,
        'label_padding_bottom' => 1,
        'label_padding_left' => 2,
        'label_content_gap' => 0.2,
        'label_owner_font_size' => 6,
        'label_sku_font_size' => 6,
    ],

    'label_orientations' => [
        'horizontal' => 'horizontal',
        'vertical' => 'vertical',
    ],

    'search' => [
        'debounce_ms' => 200,
        'min_query_length' => 1,
    ],
];
