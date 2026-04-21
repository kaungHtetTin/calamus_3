<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
                if (Schema::hasColumn('posts', 'blog_title')) {
                    $table->string('blog_title', 1000)->default('')->change();
                }
            });
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
                if (Schema::hasColumn('posts', 'blog_title')) {
                    // Reverting to previous state (no default, not null)
                    // Note: In Laravel migrations, returning to 'no default' is done by omitting it
                    $table->string('blog_title', 1000)->default(null)->change();
                }
            });
        }
    }
};
