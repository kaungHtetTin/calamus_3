<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_data')) {
            return;
        }

        if (!Schema::hasColumn('user_data', 'is_registration')) {
            return;
        }

        Schema::table('user_data', function (Blueprint $table) {
            $table->dropColumn('is_registration');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('user_data')) {
            return;
        }

        if (Schema::hasColumn('user_data', 'is_registration')) {
            return;
        }

        Schema::table('user_data', function (Blueprint $table) {
            $table->tinyInteger('is_registration')->default(0)->after('major');
        });
    }
};

