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
        Schema::table('package_plans', function (Blueprint $table) {
            $table->renameColumn('status', 'active');
        });

        // Convert existing enum values to boolean-compatible values
        DB::statement("UPDATE package_plans SET active = CASE WHEN active = 'active' THEN 1 ELSE 0 END");

        Schema::table('package_plans', function (Blueprint $table) {
            $table->boolean('active')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('package_plans', function (Blueprint $table) {
            $table->string('active')->default('active')->change();
        });

        // Convert boolean-compatible values back to enum strings
        DB::statement("UPDATE package_plans SET active = CASE WHEN active = '1' OR active = 'true' THEN 'active' ELSE 'inactive' END");

        Schema::table('package_plans', function (Blueprint $table) {
            $table->renameColumn('active', 'status');
        });
    }
};
