<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                // vimeo
                if (Schema::hasColumn('posts', 'vimeo')) {
                    $table->string('vimeo', 500)->default('')->change();
                }
                
                // view_count
                if (Schema::hasColumn('posts', 'view_count')) {
                    $table->integer('view_count')->default(0)->change();
                }

                // show_on_blog
                if (Schema::hasColumn('posts', 'show_on_blog')) {
                    $table->integer('show_on_blog')->default(0)->change();
                }
            });

            // Use raw SQL for tinyint columns to avoid Doctrine DBAL issues
            if (Schema::hasColumn('posts', 'has_video')) {
                DB::statement("ALTER TABLE posts ALTER has_video SET DEFAULT 0");
            }
            if (Schema::hasColumn('posts', 'hide')) {
                DB::statement("ALTER TABLE posts ALTER hide SET DEFAULT 0");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (Schema::hasColumn('posts', 'vimeo')) {
                    $table->string('vimeo', 500)->default(null)->change();
                }
                if (Schema::hasColumn('posts', 'view_count')) {
                    $table->integer('view_count')->default(null)->change();
                }
                if (Schema::hasColumn('posts', 'show_on_blog')) {
                    $table->integer('show_on_blog')->default(null)->change();
                }
            });

            if (Schema::hasColumn('posts', 'has_video')) {
                DB::statement("ALTER TABLE posts ALTER has_video DROP DEFAULT");
            }
            if (Schema::hasColumn('posts', 'hide')) {
                DB::statement("ALTER TABLE posts ALTER hide DROP DEFAULT");
            }
        }
    }
};
