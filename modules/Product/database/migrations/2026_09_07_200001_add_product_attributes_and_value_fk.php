<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->boolean('used_for_variations')->default(false);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'attribute_id']);
            });
        }

        if (Schema::hasTable('product_attribute_values')
            && ! Schema::hasColumn('product_attribute_values', 'attribute_value_id')) {
            Schema::table('product_attribute_values', function (Blueprint $table): void {
                $table->foreignId('attribute_value_id')
                    ->nullable()
                    ->constrained('attribute_values')
                    ->nullOnDelete();
            });
        }

        $this->replaceProductAttributeValueUnique();
    }

    public function down(): void
    {
        if (Schema::hasTable('product_attribute_values')) {
            if (Schema::hasIndex('product_attribute_values', 'product_attribute_values_value_unique')) {
                Schema::table('product_attribute_values', function (Blueprint $table): void {
                    $table->dropUnique('product_attribute_values_value_unique');
                });
            }

            if (! Schema::hasIndex('product_attribute_values', 'product_attribute_values_unique')) {
                Schema::table('product_attribute_values', function (Blueprint $table): void {
                    $table->unique(
                        ['product_id', 'attribute_id', 'product_variant_id'],
                        'product_attribute_values_unique',
                    );
                });
            }

            if (Schema::hasColumn('product_attribute_values', 'attribute_value_id')) {
                Schema::table('product_attribute_values', function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('attribute_value_id');
                });
            }
        }

        Schema::dropIfExists('product_attributes');
    }

    private function replaceProductAttributeValueUnique(): void
    {
        if (! Schema::hasTable('product_attribute_values')) {
            return;
        }

        if (Schema::hasIndex('product_attribute_values', 'product_attribute_values_unique')) {
            Schema::table('product_attribute_values', function (Blueprint $table): void {
                $table->dropUnique('product_attribute_values_unique');
            });
        }

        if (! Schema::hasIndex('product_attribute_values', 'product_attribute_values_value_unique')) {
            Schema::table('product_attribute_values', function (Blueprint $table): void {
                $table->unique(
                    ['product_id', 'attribute_id', 'product_variant_id', 'attribute_value_id'],
                    'product_attribute_values_value_unique',
                );
            });
        }
    }
};
