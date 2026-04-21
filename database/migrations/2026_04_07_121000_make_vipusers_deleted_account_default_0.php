<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vipusers') || !Schema::hasColumn('vipusers', 'deleted_account')) {
            return;
        }

        $columnType = Schema::getColumnType('vipusers', 'deleted_account');

        DB::table('vipusers')->whereNull('deleted_account')->update(['deleted_account' => 0]);

        Schema::table('vipusers', function (Blueprint $table) use ($columnType) {
            if ($columnType === 'boolean') {
                $table->boolean('deleted_account')->default(0)->change();
                return;
            }

            if ($columnType === 'integer') {
                $table->integer('deleted_account')->default(0)->change();
                return;
            }

            if ($columnType === 'bigint') {
                $table->bigInteger('deleted_account')->default(0)->change();
                return;
            }

            $table->integer('deleted_account')->default(0)->change();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('vipusers') || !Schema::hasColumn('vipusers', 'deleted_account')) {
            return;
        }

        $columnType = Schema::getColumnType('vipusers', 'deleted_account');

        Schema::table('vipusers', function (Blueprint $table) use ($columnType) {
            if ($columnType === 'boolean') {
                $table->boolean('deleted_account')->default(null)->change();
                return;
            }

            if ($columnType === 'integer') {
                $table->integer('deleted_account')->default(null)->change();
                return;
            }

            if ($columnType === 'bigint') {
                $table->bigInteger('deleted_account')->default(null)->change();
                return;
            }

            $table->integer('deleted_account')->default(null)->change();
        });
    }
};

