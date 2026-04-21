<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('speaking_dialogue_titles') || ! Schema::hasColumn('speaking_dialogue_titles', 'legacy_id')) {
            return;
        }

        $uniqueIndexName = null;
        $legacyIdIndexName = null;

        try {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes('speaking_dialogue_titles');

            foreach ($indexes as $name => $index) {
                $columns = $index->getColumns();
                if ($index->isUnique() && $columns === ['major', 'legacy_id']) {
                    $uniqueIndexName = $name;
                }
                if (! $index->isUnique() && $columns === ['legacy_id']) {
                    $legacyIdIndexName = $name;
                }
            }
        } catch (\Throwable $e) {
        }

        Schema::table('speaking_dialogue_titles', function (Blueprint $table) use ($uniqueIndexName, $legacyIdIndexName) {
            if ($uniqueIndexName) {
                try {
                    $table->dropUnique($uniqueIndexName);
                } catch (\Throwable $e) {
                }
            } else {
                try {
                    $table->dropUnique(['major', 'legacy_id']);
                } catch (\Throwable $e) {
                }
            }

            if ($legacyIdIndexName) {
                try {
                    $table->dropIndex($legacyIdIndexName);
                } catch (\Throwable $e) {
                }
            } else {
                try {
                    $table->dropIndex(['legacy_id']);
                } catch (\Throwable $e) {
                }
            }

            $table->dropColumn('legacy_id');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('speaking_dialogue_titles') || Schema::hasColumn('speaking_dialogue_titles', 'legacy_id')) {
            return;
        }

        Schema::table('speaking_dialogue_titles', function (Blueprint $table) {
            $table->bigInteger('legacy_id')->nullable()->after('major');
            $table->index('legacy_id');
            $table->unique(['major', 'legacy_id']);
        });
    }
};
