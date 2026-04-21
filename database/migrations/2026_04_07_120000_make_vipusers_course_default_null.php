<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vipusers') || !Schema::hasColumn('vipusers', 'course')) {
            return;
        }

        $columnType = Schema::getColumnType('vipusers', 'course');

        Schema::table('vipusers', function (Blueprint $table) use ($columnType) {
            if ($columnType === 'string') {
                $table->string('course')->nullable()->default(null)->change();
                return;
            }

            if ($columnType === 'integer') {
                $table->integer('course')->nullable()->default(null)->change();
                return;
            }

            if ($columnType === 'bigint') {
                $table->bigInteger('course')->nullable()->default(null)->change();
                return;
            }

            $table->string('course')->nullable()->change();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('vipusers') || !Schema::hasColumn('vipusers', 'course')) {
            return;
        }

        $columnType = Schema::getColumnType('vipusers', 'course');

        if ($columnType === 'string') {
            DB::table('vipusers')->whereNull('course')->update(['course' => '']);
        }

        if (in_array($columnType, ['integer', 'bigint'], true)) {
            DB::table('vipusers')->whereNull('course')->update(['course' => 0]);
        }

        Schema::table('vipusers', function (Blueprint $table) use ($columnType) {
            if ($columnType === 'string') {
                $table->string('course')->nullable(false)->default('')->change();
                return;
            }

            if ($columnType === 'integer') {
                $table->integer('course')->nullable(false)->default(0)->change();
                return;
            }

            if ($columnType === 'bigint') {
                $table->bigInteger('course')->nullable(false)->default(0)->change();
                return;
            }

            $table->string('course')->nullable(false)->change();
        });
    }
};

