<?php

return [

    /*
    |--------------------------------------------------------------------------
    | White-label token overrides
    |--------------------------------------------------------------------------
    |
    | Override semantic design tokens without touching components or business
    | logic. Keys map to CSS custom properties: "primary" → --color-primary.
    |
    | Set via .env (e.g. DESIGN_COLOR_PRIMARY=#0ea5e9) or directly here.
    | Null values are ignored.
    |
    */

    'overrides' => array_filter([
        'primary' => env('DESIGN_COLOR_PRIMARY'),
        'primary-hover' => env('DESIGN_COLOR_PRIMARY_HOVER'),
        'primary-active' => env('DESIGN_COLOR_PRIMARY_ACTIVE'),
        'background' => env('DESIGN_COLOR_BACKGROUND'),
        'surface' => env('DESIGN_COLOR_SURFACE'),
        'sidebar' => env('DESIGN_COLOR_SIDEBAR'),
    ], fn ($value) => filled($value)),

];
