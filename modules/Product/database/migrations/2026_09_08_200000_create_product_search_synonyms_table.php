<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_search_synonyms', function (Blueprint $table): void {
            $table->id();
            $table->string('from_term')->unique();
            $table->string('to_term');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_search_synonyms');
    }
};
