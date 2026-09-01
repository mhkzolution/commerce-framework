<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_login_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['identifier', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_login_otps');
    }
};
