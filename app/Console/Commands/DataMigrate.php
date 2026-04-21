<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DataMigrate extends Command
{
    protected $signature = 'data:migrate {--only= : legacy,learners_user_id,token_json,vocab_user_id or comma-separated list}';

    protected $description = 'Run data migrations (legacy import + data conversions) separately from structure migrations';

    public function handle()
    {
        $only = strtolower(trim((string) $this->option('only')));
        $requested = $only === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $only))));

        $this->info('Starting data migration...');

        $plan = [
            'learners_user_id' => fn () => $this->migrateLearnersUserIdFromLearnerPhone(),
            'legacy' => fn () => $this->runLegacyMigrateData(),
            'token_json' => fn () => $this->finalizeUserDataTokenJson(),
            'vocab_user_id' => fn () => $this->migrateVocabTablesUserId(),
        ];

        $toRun = $requested === [] ? array_keys($plan) : array_values(array_intersect(array_keys($plan), $requested));

        $unknown = array_values(array_diff($requested, array_keys($plan)));
        if ($unknown !== []) {
            $this->error('Unknown --only values: '.implode(', ', $unknown));
            $this->line('Allowed values: '.implode(', ', array_keys($plan)));
            return Command::INVALID;
        }

        foreach ($toRun as $step) {
            $this->newLine();
            $this->info('Step: '.$step);
            $startedAt = microtime(true);
            $ok = (bool) $plan[$step]();
            $elapsed = microtime(true) - $startedAt;
            $this->line('Step duration: '.number_format($elapsed, 2).'s');
            if (!$ok) {
                $this->warn('Step failed or partially applied: '.$step);
            }
        }

        $this->newLine();
        $this->info('Data migration completed.');
        return Command::SUCCESS;
    }

    private function runLegacyMigrateData(): bool
    {
        $this->info('Running legacy:migrate-data ...');
        try {
            $exitCode = (int) $this->call('legacy:migrate-data');
            $this->info('legacy:migrate-data exit code: '.(int) $exitCode);
            return (int) $exitCode === 0;
        } catch (\Throwable $e) {
            $this->error('legacy:migrate-data failed: '.$e->getMessage());
            return false;
        }
    }

    private function migrateLearnersUserIdFromLearnerPhone(): bool
    {
        if (!Schema::hasTable('learners') || !Schema::hasColumn('learners', 'user_id') || !Schema::hasColumn('learners', 'learner_phone')) {
            $this->warn('Skipped: learners table/columns not ready.');
            return true;
        }

        if (DB::getDriverName() !== 'mysql') {
            $this->warn('Skipped: only supported on mysql driver.');
            return true;
        }

        $safeNumeric = "
            learner_phone REGEXP '^[0-9]+$'
            AND (
                CHAR_LENGTH(learner_phone) < 20
                OR (CHAR_LENGTH(learner_phone) = 20 AND learner_phone <= '18446744073709551615')
            )
        ";

        try {
            $affected = DB::affectingStatement("
                UPDATE learners
                SET user_id = CAST(learner_phone AS UNSIGNED)
                WHERE (user_id IS NULL OR user_id = 0)
                  AND learner_phone IS NOT NULL
                  AND {$safeNumeric}
            ");

            $skipped = (int) (DB::selectOne("
                SELECT COUNT(*) AS c
                FROM learners
                WHERE (user_id IS NULL OR user_id = 0)
                  AND learner_phone IS NOT NULL
                  AND NOT ({$safeNumeric})
            ")->c ?? 0);

            $this->info('Updated learners rows: '.(int) $affected);
            if ($skipped > 0) {
                $this->warn('Skipped learners rows (invalid/overflow learner_phone): '.$skipped);
            }

            return true;
        } catch (\Throwable $e) {
            $this->error('Failed updating learners.user_id: '.$e->getMessage());
            return false;
        }
    }

    private function finalizeUserDataTokenJson(): bool
    {
        if (!Schema::hasTable('user_data')) {
            $this->warn('Skipped: user_data table not found.');
            return true;
        }

        if (!Schema::hasColumn('user_data', 'token')) {
            $this->warn('Skipped: user_data.token column not found.');
            return true;
        }

        if (DB::getDriverName() !== 'mysql') {
            $this->warn('Skipped: only supported on mysql driver.');
            return true;
        }

        $db = DB::getDatabaseName();
        $type = null;
        try {
            $type = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $db)
                ->where('TABLE_NAME', 'user_data')
                ->where('COLUMN_NAME', 'token')
                ->value('DATA_TYPE');
            $type = strtolower((string) $type);
        } catch (\Throwable $e) {
        }

        if ($type === 'json') {
            $this->info('user_data.token is already JSON. Nothing to do.');
            return true;
        }

        if (!Schema::hasColumn('user_data', 'token_new')) {
            Schema::table('user_data', function (Blueprint $table) {
                $table->json('token_new')->nullable()->after('token');
            });
        }

        $this->info('Converting user_data.token -> token_new ...');

        try {
            DB::table('user_data')
                ->whereNotNull('token')
                ->where('token', '!=', '')
                ->whereNull('token_new')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $raw = $row->token;

                        $isJson = false;
                        $decoded = null;
                        if (is_string($raw)) {
                            $decoded = json_decode($raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $isJson = true;
                            }
                        }

                        $tokenData = $isJson ? $decoded : ['android' => $raw];

                        DB::table('user_data')
                            ->where('id', $row->id)
                            ->update(['token_new' => json_encode($tokenData)]);
                    }
                    $this->output->write('.');
                });
            $this->newLine();
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Failed converting tokens: '.$e->getMessage());
            return false;
        }

        if (Schema::hasColumn('user_data', 'token') && Schema::hasColumn('user_data', 'token_new')) {
            $this->info('Finalizing columns (drop token, rename token_new -> token)...');
            try {
                Schema::table('user_data', function (Blueprint $table) {
                    $table->dropColumn('token');
                });
            } catch (\Throwable $e) {
                $this->warn('Drop token failed: '.$e->getMessage());
                return false;
            }

            try {
                Schema::table('user_data', function (Blueprint $table) {
                    $table->renameColumn('token_new', 'token');
                });
            } catch (\Throwable $e) {
                $this->warn('Rename token_new failed: '.$e->getMessage());
                return false;
            }
        }

        $this->info('Token JSON migration done.');
        return true;
    }

    private function migrateVocabTablesUserId(): bool
    {
        if (!Schema::hasTable('learners') || !Schema::hasColumn('learners', 'id') || !Schema::hasColumn('learners', 'user_id')) {
            $this->warn('Skipped: learners table/columns not ready.');
            return true;
        }

        if (DB::getDriverName() !== 'mysql') {
            $this->warn('Skipped: only supported on mysql driver.');
            return true;
        }

        $ok = true;

        if (Schema::hasTable('user_card_states') && Schema::hasColumn('user_card_states', 'user_id')) {
            try {
                $affected = DB::affectingStatement("
                    UPDATE user_card_states ucs
                    INNER JOIN learners l ON l.id = CAST(ucs.user_id AS UNSIGNED)
                    SET ucs.user_id = l.user_id
                    WHERE ucs.user_id IS NOT NULL AND l.user_id IS NOT NULL
                ");
                $this->info('Updated user_card_states rows: '.(int) $affected);
            } catch (\Throwable $e) {
                $this->warn('user_card_states update failed: '.$e->getMessage());
                $ok = false;
            }
        } else {
            $this->warn('Skipped: user_card_states table/column not found.');
        }

        if (Schema::hasTable('user_word_skips') && Schema::hasColumn('user_word_skips', 'user_id')) {
            try {
                $affected = DB::affectingStatement("
                    UPDATE user_word_skips uws
                    INNER JOIN learners l ON l.id = CAST(uws.user_id AS UNSIGNED)
                    SET uws.user_id = l.user_id
                    WHERE uws.user_id IS NOT NULL AND l.user_id IS NOT NULL
                ");
                $this->info('Updated user_word_skips rows: '.(int) $affected);
            } catch (\Throwable $e) {
                $this->warn('user_word_skips update failed: '.$e->getMessage());
                $ok = false;
            }
        } else {
            $this->warn('Skipped: user_word_skips table/column not found.');
        }

        return $ok;
    }
}

