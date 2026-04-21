<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                if (!Schema::hasColumn('lessons', 'like_count')) {
                    $table->integer('like_count')->default(0)->after('thumbnail');
                }
                if (!Schema::hasColumn('lessons', 'comment_count')) {
                    $table->integer('comment_count')->default(0)->after('like_count');
                }
                if (!Schema::hasColumn('lessons', 'share_count')) {
                    $table->integer('share_count')->default(0)->after('comment_count');
                }
                if (!Schema::hasColumn('lessons', 'view_count')) {
                    $table->integer('view_count')->default(0)->after('share_count');
                }
                if (!Schema::hasColumn('lessons', 'download_url')) {
                    $table->string('download_url', 1000)->nullable()->after('link');
                }
            });
        }

        if (Schema::hasTable('comment')) {
            Schema::table('comment', function (Blueprint $table) {
                if (!Schema::hasColumn('comment', 'target_type')) {
                    $table->string('target_type', 20)->nullable()->after('post_id');
                }
                if (!Schema::hasColumn('comment', 'target_id')) {
                    $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
                }
            });

            Schema::table('comment', function (Blueprint $table) {
                try {
                    $table->index(['target_type', 'target_id'], 'comment_target_type_target_id_idx');
                } catch (\Throwable $e) {
                    // Index may already exist in some environments.
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('comment')) {
            Schema::table('comment', function (Blueprint $table) {
                try {
                    $table->dropIndex('comment_target_type_target_id_idx');
                } catch (\Throwable $e) {
                    // Ignore missing index.
                }

                if (Schema::hasColumn('comment', 'target_id')) {
                    $table->dropColumn('target_id');
                }
                if (Schema::hasColumn('comment', 'target_type')) {
                    $table->dropColumn('target_type');
                }
            });
        }

        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                $dropCols = [];
                foreach (['like_count', 'comment_count', 'share_count', 'view_count', 'download_url'] as $col) {
                    if (Schema::hasColumn('lessons', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if (!empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
            });
        }
    }
};

