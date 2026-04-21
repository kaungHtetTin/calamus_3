<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('languages')) {
            return;
        }

        if (!Schema::hasColumn('languages', 'firebase_topic_user')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->string('firebase_topic_user', 150)->nullable();
            });
        }

        if (!Schema::hasColumn('languages', 'firebase_topic_admin')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->string('firebase_topic_admin', 150)->nullable();
            });
        }

        if (Schema::hasColumn('languages', 'firebase_topic')) {
            DB::statement("
                UPDATE languages
                SET firebase_topic_user = firebase_topic
                WHERE (firebase_topic_user IS NULL OR firebase_topic_user = '')
                  AND firebase_topic IS NOT NULL
                  AND firebase_topic != ''
            ");

            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('firebase_topic');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('languages')) {
            return;
        }

        if (!Schema::hasColumn('languages', 'firebase_topic')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->string('firebase_topic', 150)->nullable();
            });
        }

        if (Schema::hasColumn('languages', 'firebase_topic_user')) {
            DB::statement("
                UPDATE languages
                SET firebase_topic = firebase_topic_user
                WHERE (firebase_topic IS NULL OR firebase_topic = '')
                  AND firebase_topic_user IS NOT NULL
                  AND firebase_topic_user != ''
            ");
        }

        if (Schema::hasColumn('languages', 'firebase_topic_admin')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('firebase_topic_admin');
            });
        }

        if (Schema::hasColumn('languages', 'firebase_topic_user')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('firebase_topic_user');
            });
        }
    }
};
