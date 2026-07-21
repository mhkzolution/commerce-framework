<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('tenant_id')->nullable()->after('uuid');
            $table->string('status', 20)->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->json('meta')->nullable()->after('remember_token');
            $table->softDeletes();
        });

        foreach (\Illuminate\Support\Facades\DB::table('users')->whereNull('uuid')->pluck('id') as $userId) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $userId)
                ->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['uuid', 'tenant_id', 'status', 'last_login_at', 'meta']);
        });
    }
};
