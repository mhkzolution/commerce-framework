<?php

declare(strict_types=1);

namespace Commerce\Barcode\Models;

use Commerce\Barcode\Support\BarcodeLabelStyle;
use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarcodeTemplate extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'preset_code',
        'paper_size',
        'rows',
        'columns',
        'margin_top',
        'margin_right',
        'margin_bottom',
        'margin_left',
        'spacing_horizontal',
        'spacing_vertical',
        'label_width',
        'label_height',
        'label_orientation',
        'label_padding_top',
        'label_padding_right',
        'label_padding_bottom',
        'label_padding_left',
        'label_content_gap',
        'label_owner_font_size',
        'label_sku_font_size',
        'show_name',
        'show_sku',
        'show_owner',
        'show_barcode',
        'is_favorite',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'rows' => 'integer',
            'columns' => 'integer',
            'margin_top' => 'float',
            'margin_right' => 'float',
            'margin_bottom' => 'float',
            'margin_left' => 'float',
            'spacing_horizontal' => 'float',
            'spacing_vertical' => 'float',
            'label_width' => 'float',
            'label_height' => 'float',
            'label_padding_top' => 'float',
            'label_padding_right' => 'float',
            'label_padding_bottom' => 'float',
            'label_padding_left' => 'float',
            'label_content_gap' => 'float',
            'label_owner_font_size' => 'float',
            'label_sku_font_size' => 'float',
            'show_name' => 'boolean',
            'show_sku' => 'boolean',
            'show_owner' => 'boolean',
            'show_barcode' => 'boolean',
            'is_favorite' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(BarcodePrintJob::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettingsArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'preset_code' => $this->preset_code,
            'paper_size' => $this->paper_size,
            'paper_size_label' => config("barcode.paper_sizes.{$this->paper_size}.label", strtoupper((string) $this->paper_size)),
            'paper_width_mm' => config("barcode.paper_sizes.{$this->paper_size}.width_mm"),
            'paper_height_mm' => config("barcode.paper_sizes.{$this->paper_size}.height_mm"),
            'rows' => $this->rows,
            'columns' => $this->columns,
            'margin_top' => $this->margin_top,
            'margin_right' => $this->margin_right,
            'margin_bottom' => $this->margin_bottom,
            'margin_left' => $this->margin_left,
            'spacing_horizontal' => $this->spacing_horizontal,
            'spacing_vertical' => $this->spacing_vertical,
            'label_width' => $this->label_width,
            'label_height' => $this->label_height,
            'label_orientation' => $this->label_orientation ?? 'vertical',
            ...BarcodeLabelStyle::settingsKeys($this->toArray()),
            'show_name' => $this->show_name ?? true,
            'show_sku' => $this->show_sku ?? true,
            'show_owner' => $this->show_owner ?? true,
            'show_barcode' => $this->show_barcode ?? true,
            'is_favorite' => $this->is_favorite,
            'is_default' => $this->is_default,
        ];
    }
}
