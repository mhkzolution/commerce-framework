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
            'paper_size' => $this->paper_size,
            'paper_size_label' => config("barcode.paper_sizes.{$this->paper_size}.label", strtoupper($this->paper_size)),
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
            'is_favorite' => $this->is_favorite,
            'is_default' => $this->is_default,
        ];
    }
}
