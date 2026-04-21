<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('artists')) {
            return;
        }

        if (!Schema::hasColumn('artists', 'image_slug')) {
            return;
        }

        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn('image_slug');
        });
    }

    public function down()
    {
    }
};

