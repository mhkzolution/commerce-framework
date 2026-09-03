<?php

declare(strict_types=1);

return [
    'paper_sizes' => [
        'a4' => [
            'label' => 'A4',
            'width_mm' => 210,
            'height_mm' => 297,
        ],
        'thermal' => [
            'label' => 'Thermal 50×30',
            'width_mm' => 50,
            'height_mm' => 30,
        ],
    ],

    'presets' => [
        'a4_40' => [
            'name' => 'A4 40 Labels',
            'paper_size' => 'a4',
            'rows' => 10,
            'columns' => 4,
            'label_width' => 48.5,
            'label_height' => 25.4,
            'spacing_horizontal' => 2,
            'spacing_vertical' => 2,
            'margin_top' => 12.5,
            'margin_right' => 5,
            'margin_bottom' => 12.5,
            'margin_left' => 5,
        ],
        'a4_24' => [
            'name' => 'A4 24 Labels',
            'paper_size' => 'a4',
            'rows' => 8,
            'columns' => 3,
            'label_width' => 63.5,
            'label_height' => 33.9,
            'spacing_horizontal' => 2,
            'spacing_vertical' => 2,
            'margin_top' => 5.9,
            'margin_right' => 7.75,
            'margin_bottom' => 5.9,
            'margin_left' => 7.75,
        ],
        'a4_65' => [
            'name' => 'A4 65 Labels',
            'paper_size' => 'a4',
            'rows' => 13,
            'columns' => 5,
            'label_width' => 38.1,
            'label_height' => 21.2,
            'spacing_horizontal' => 0,
            'spacing_vertical' => 0,
            'margin_top' => 10.7,
            'margin_right' => 9.75,
            'margin_bottom' => 10.7,
            'margin_left' => 9.75,
        ],
        'thermal_50x30' => [
            'name' => 'Thermal 50×30',
            'paper_size' => 'thermal',
            'rows' => 1,
            'columns' => 1,
            'label_width' => 50,
            'label_height' => 30,
            'spacing_horizontal' => 0,
            'spacing_vertical' => 0,
            'margin_top' => 0,
            'margin_right' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
        ],
    ],

    'default_template' => [
        'name' => 'A4 40 Labels',
        'preset_code' => 'a4_40',
        'paper_size' => 'a4',
        'rows' => 10,
        'columns' => 4,
        'margin_top' => 12.5,
        'margin_right' => 5,
        'margin_bottom' => 12.5,
        'margin_left' => 5,
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
