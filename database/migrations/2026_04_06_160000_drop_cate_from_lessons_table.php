<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lessons')) {
            return;
        }

        if (Schema::hasColumn('lessons', 'cate')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('cate');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('lessons')) {
            return;
        }

        if (!Schema::hasColumn('lessons', 'cate')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->string('cate', 100)->nullable()->after('major');
            });
        }
    }
};
