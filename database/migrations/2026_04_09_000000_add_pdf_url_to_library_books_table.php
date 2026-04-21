<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('library_books')) {
            return;
        }

        if (!Schema::hasColumn('library_books', 'pdf_url')) {
            Schema::table('library_books', function (Blueprint $table) {
                if (Schema::hasColumn('library_books', 'pdf_file')) {
                    $table->string('pdf_url', 1000)->nullable()->after('pdf_file');
                } else {
                    $table->string('pdf_url', 1000)->nullable();
                }
            });
        }

        $base = 'https://www.calamuseducation.com/';

        DB::table('library_books')
            ->select('id', 'pdf_file', 'pdf_url')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($base) {
                foreach ($rows as $row) {
                    $id = (int) $row->id;
                    $current = trim((string) ($row->pdf_url ?? ''));
                    if ($current !== '' && (str_starts_with($current, 'http://') || str_starts_with($current, 'https://'))) {
                        continue;
                    }

                    $source = $current !== '' ? $current : trim((string) ($row->pdf_file ?? ''));
                    if ($source === '') {
                        continue;
                    }

                    if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
                        DB::table('library_books')->where('id', $id)->update(['pdf_url' => $source]);
                        continue;
                    }

                    $path = ltrim($source, '/');
                    DB::table('library_books')->where('id', $id)->update(['pdf_url' => $base . $path]);
                }
            });
    }

    public function down()
    {
        if (!Schema::hasTable('library_books')) {
            return;
        }

        if (Schema::hasColumn('library_books', 'pdf_url')) {
            Schema::table('library_books', function (Blueprint $table) {
                $table->dropColumn('pdf_url');
            });
        }
    }
};

