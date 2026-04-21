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
        Schema::table('apps', function (Blueprint $table) {
            $table->string('platform')->default('android')->after('major');
            $table->integer('latest_version_code')->nullable()->after('platform');
            $table->string('latest_version_name')->nullable()->after('latest_version_code');
            $table->integer('min_version_code')->nullable()->after('latest_version_name');
            $table->text('update_message')->nullable()->after('min_version_code');
            $table->boolean('force_update')->default(false)->after('update_message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn([
                'platform',
                'latest_version_code',
                'latest_version_name',
                'min_version_code',
                'update_message',
                'force_update'
            ]);
        });
    }
};
