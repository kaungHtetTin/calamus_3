<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'certificate_code')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->string('certificate_code', 10)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'certificate_code')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->string('certificate_code', 5)->change();
        });
    }
};

