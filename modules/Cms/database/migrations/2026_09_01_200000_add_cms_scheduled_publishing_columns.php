<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_posts') && ! Schema::hasColumn('cms_posts', 'unpublish_at')) {
            Schema::table('cms_posts', function (Blueprint $table): void {
                $table->timestamp('unpublish_at')->nullable()->after('published_at');
            });
        }

        if (Schema::hasTable('cms_posts')) {
            $this->addIndexIfMissing('cms_posts', ['status', 'published_at']);
            $this->addIndexIfMissing('cms_posts', ['status', 'unpublish_at']);
        }

        if (Schema::hasTable('cms_pages')) {
            if (! Schema::hasColumn('cms_pages', 'published_at')) {
                Schema::table('cms_pages', function (Blueprint $table): void {
                    $table->timestamp('published_at')->nullable()->after('status');
                });
            }

            if (! Schema::hasColumn('cms_pages', 'unpublish_at')) {
                Schema::table('cms_pages', function (Blueprint $table): void {
                    $table->timestamp('unpublish_at')->nullable()->after('published_at');
                });
            }

            $this->addIndexIfMissing('cms_pages', ['status', 'published_at']);
            $this->addIndexIfMissing('cms_pages', ['status', 'unpublish_at']);
        }

        $now = now();

        if (Schema::hasTable('cms_posts')) {
            DB::table('cms_posts')
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '>', $now)
                ->update(['status' => 'scheduled']);
        }

        if (Schema::hasTable('cms_pages') && Schema::hasColumn('cms_pages', 'published_at')) {
            DB::table('cms_pages')
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '>', $now)
                ->update(['status' => 'scheduled']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_posts') && Schema::hasColumn('cms_posts', 'unpublish_at')) {
            Schema::table('cms_posts', function (Blueprint $table): void {
                $this->dropIndexIfExists($table, 'cms_posts', ['status', 'published_at']);
                $this->dropIndexIfExists($table, 'cms_posts', ['status', 'unpublish_at']);
                $table->dropColumn('unpublish_at');
            });
        }

        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table): void {
                $this->dropIndexIfExists($table, 'cms_pages', ['status', 'published_at']);
                $this->dropIndexIfExists($table, 'cms_pages', ['status', 'unpublish_at']);
                if (Schema::hasColumn('cms_pages', 'unpublish_at')) {
                    $table->dropColumn('unpublish_at');
                }
                if (Schema::hasColumn('cms_pages', 'published_at')) {
                    $table->dropColumn('published_at');
                }
            });
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndexIfMissing(string $table, array $columns): void
    {
        if (Schema::hasIndex($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->index($columns);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropIndexIfExists(Blueprint $table, string $tableName, array $columns): void
    {
        if (! Schema::hasIndex($tableName, $columns)) {
            return;
        }

        $table->dropIndex($columns);
    }
};
