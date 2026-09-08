<?php

declare(strict_types=1);

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attribute_values')) {
            Schema::create('attribute_values', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->string('code');
                $table->string('label');
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['tenant_id', 'attribute_id', 'code']);
            });
        }

        $this->backfillFromOptions();
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }

    private function backfillFromOptions(): void
    {
        $service = app(AttributeValueService::class);

        Attribute::query()->each(function (Attribute $attribute) use ($service): void {
            $options = $attribute->options;
            if (! is_array($options) || $options === []) {
                return;
            }

            foreach (array_values($options) as $position => $label) {
                if (! is_string($label) && ! is_numeric($label)) {
                    continue;
                }

                $label = (string) $label;

                $exists = AttributeValue::query()
                    ->where('attribute_id', $attribute->id)
                    ->where('position', $position)
                    ->where('label', $label)
                    ->exists();

                if ($exists) {
                    continue;
                }

                AttributeValue::query()->create([
                    'tenant_id' => $attribute->tenant_id,
                    'attribute_id' => $attribute->id,
                    'code' => $service->allocateCode($attribute->id, $label),
                    'label' => $label,
                    'position' => $position,
                ]);
            }
        });
    }
};
