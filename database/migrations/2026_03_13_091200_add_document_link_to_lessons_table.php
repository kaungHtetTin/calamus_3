<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lessons')) {
            return;
        }

        if (!Schema::hasColumn('lessons', 'document_link')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->string('document_link')->nullable()->after('download_url');
            });
        }

        DB::table('lessons')
            ->select('id')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $lessonId = (int)$row->id;
                    DB::table('lessons')
                        ->where('id', $lessonId)
                        ->update([
                            'document_link' => 'https://www.calamuseducation.com/uploads/lessons/html/' . $lessonId . '.html',
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('lessons')) {
            return;
        }

        if (Schema::hasColumn('lessons', 'document_link')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('document_link');
            });
        }
    }
};

