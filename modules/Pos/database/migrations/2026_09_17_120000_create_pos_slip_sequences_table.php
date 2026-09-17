<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_slip_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0);
            $table->date('slip_date');
            $table->unsignedInteger('last_value')->default(0);

            $table->unique(['tenant_id', 'slip_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_slip_sequences');
    }
};
