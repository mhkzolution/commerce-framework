<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('index_name', 100);
            $table->string('document_id', 100);
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['index_name', 'document_id']);
            $table->index(['index_name', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_documents');
    }
};
