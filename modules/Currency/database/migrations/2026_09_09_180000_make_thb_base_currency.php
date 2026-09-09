<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const int RATE_PRECISION = 1_000_000;

    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        $thb = DB::table('currencies')->where('code', 'THB')->first();
        if ($thb === null || (bool) $thb->is_base) {
            return;
        }

        $thbRate = max(1, (int) $thb->rate_micro);

        foreach (DB::table('currencies')->orderBy('id')->get() as $currency) {
            $isThb = $currency->code === 'THB';

            DB::table('currencies')->where('id', $currency->id)->update([
                'is_base' => $isThb,
                'rate_micro' => $isThb
                    ? self::RATE_PRECISION
                    : max(1, (int) round(((int) $currency->rate_micro) * self::RATE_PRECISION / $thbRate)),
                'sort_order' => $isThb ? 1 : max(2, (int) $currency->sort_order),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        $usd = DB::table('currencies')->where('code', 'USD')->first();
        if ($usd === null || (bool) $usd->is_base) {
            return;
        }

        $usdRate = max(1, (int) $usd->rate_micro);

        foreach (DB::table('currencies')->orderBy('id')->get() as $currency) {
            $isUsd = $currency->code === 'USD';

            DB::table('currencies')->where('id', $currency->id)->update([
                'is_base' => $isUsd,
                'rate_micro' => $isUsd
                    ? self::RATE_PRECISION
                    : max(1, (int) round(((int) $currency->rate_micro) * self::RATE_PRECISION / $usdRate)),
                'updated_at' => now(),
            ]);
        }
    }
};
