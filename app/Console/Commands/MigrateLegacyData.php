<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:migrate-data {--only= : Run only one section (game_words, word_of_days, user_data, friends, friend_requests, learner_ids, post_user_ids, study_user_ids, vip_user_ids, speaking_chatbot)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy JSON data (friends, likes) to normalized tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting data migration...');

        $only = $this->option('only');
        if (! empty($only)) {
            return $this->runOnlySection((string) $only);
        }

        // 0. Learner IDs (Legacy phone to user_id)
        $this->migrateLearnerIds();

        // 0.1 Post User IDs
        $this->migratePostUserIds();

        // 0.2 Study User IDs
        $this->migrateStudyUserIds();

        // 0.3 VIP User IDs
        $this->migrateVipUserIds();

        // 1. Friends
        $this->migrateFriends();

        // 2. Friend Requests
        $this->migrateFriendRequests();

        // 3. User Data
        $this->migrateUserData();

        // 4. Word of Days
        $this->migrateWordOfDays();

        // 5. Game Words
        $this->migrateGameWords();

        // 6. Speaking Chatbot
        $this->migrateSpeakingChatbot();

        $this->info('Migration completed!');

        return Command::SUCCESS;
    }

    private function runOnlySection(string $section): int
    {
        $section = strtolower(trim($section));

        $map = [
            'learner_ids' => 'migrateLearnerIds',
            'post_user_ids' => 'migratePostUserIds',
            'study_user_ids' => 'migrateStudyUserIds',
            'vip_user_ids' => 'migrateVipUserIds',
            'friends' => 'migrateFriends',
            'friend_requests' => 'migrateFriendRequests',
            'user_data' => 'migrateUserData',
            'word_of_days' => 'migrateWordOfDays',
            'game_words' => 'migrateGameWords',
            'speaking_chatbot' => 'migrateSpeakingChatbot',
        ];

        if (! isset($map[$section])) {
            $this->error("Unknown --only section: {$section}");
            $this->line('Allowed values: '.implode(', ', array_keys($map)));

            return Command::INVALID;
        }

        $method = $map[$section];
        $this->{$method}();
        $this->info('Selected migration completed.');

        return Command::SUCCESS;
    }

    private function migrateLearnerIds()
    {
        $this->info('Migrating Learner IDs (learner_phone -> user_id)...');

        if (! Schema::hasTable('learners')) {
            $this->warn('Table "learners" not found. Skipping ID migration.');

            return;
        }

        if (! Schema::hasColumn('learners', 'user_id')) {
            $this->warn('Column "user_id" not found in "learners" table. Please run migration first.');

            return;
        }

        // We can do this with a single query, much faster than chunking
        try {
            DB::statement('UPDATE learners SET user_id = learner_phone WHERE (user_id IS NULL OR user_id = 0) AND learner_phone IS NOT NULL');
            $this->info('Learner IDs updated successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to update learner IDs: '.$e->getMessage());
        }
    }

    private function migratePostUserIds()
    {
        $this->info('Migrating Post User IDs (learner_id -> user_id)...');

        if (! Schema::hasTable('posts')) {
            $this->warn('Table "posts" not found. Skipping Post ID migration.');

            return;
        }

        if (! Schema::hasColumn('posts', 'user_id')) {
            $this->warn('Column "user_id" not found in "posts" table. Please run migration first.');

            return;
        }

        try {
            DB::statement('UPDATE posts SET user_id = learner_id WHERE (user_id IS NULL OR user_id = 0) AND learner_id IS NOT NULL');
            $this->info('Post User IDs updated successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to update post user IDs: '.$e->getMessage());
        }
    }

    private function migrateStudyUserIds()
    {
        $this->info('Migrating Study User IDs (learner_id -> user_id)...');

        if (! Schema::hasTable('studies')) {
            $this->warn('Table "studies" not found. Skipping Study ID migration.');

            return;
        }

        if (! Schema::hasColumn('studies', 'user_id')) {
            $this->warn('Column "user_id" not found in "studies" table. Please run migration first.');

            return;
        }

        try {
            DB::statement('UPDATE studies SET user_id = learner_id WHERE (user_id IS NULL OR user_id = 0) AND learner_id IS NOT NULL');
            $this->info('Study User IDs updated successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to update study user IDs: '.$e->getMessage());
        }
    }

    private function migrateVipUserIds()
    {
        $this->info('Migrating VIP User IDs (phone -> user_id)...');

        if (! Schema::hasTable('vipusers')) {
            $this->warn('Table "vipusers" not found. Skipping VIP User ID migration.');

            return;
        }

        if (! Schema::hasColumn('vipusers', 'user_id')) {
            $this->warn('Column "user_id" not found in "vipusers" table. Please run migration first.');

            return;
        }

        try {
            DB::statement('UPDATE vipusers SET user_id = phone WHERE (user_id IS NULL OR user_id = 0) AND phone IS NOT NULL');
            $this->info('VIP User IDs updated successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to update VIP user IDs: '.$e->getMessage());
        }
    }

    private function migrateFriends()
    {
        $this->info('Migrating Friends...');

        // Check if legacy table exists
        if (! Schema::hasTable('friends')) {
            $this->error('Legacy table "friends" does not exist.');

            return;
        }

        DB::table('friends')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $userId = $row->user_id;

                // Legacy supported multiple majors, now we merge to global
                $majors = ['english', 'korea', 'chinese', 'japanese', 'russian'];

                foreach ($majors as $major) {
                    if (! empty($row->$major)) {
                        $friendsArr = json_decode($row->$major, true);
                        if (is_array($friendsArr)) {
                            foreach ($friendsArr as $friend) {
                                if (isset($friend['fri_id'])) {
                                    $friendId = $friend['fri_id'];

                                    try {
                                        // Use upsert instead of insertOrIgnore
                                        DB::table('friendships')->upsert(
                                            [
                                                'user_id' => $userId,
                                                'friend_id' => $friendId,
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ],
                                            ['user_id', 'friend_id'], // Unique constraint columns
                                            ['updated_at'] // Columns to update if exists
                                        );
                                    } catch (\Exception $e) {
                                        // Ignore duplicate entry errors or FK errors if user missing
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->output->write('.');
        });
        $this->newLine();
        $this->info('Friends migration done.');
    }

    private function migrateFriendRequests()
    {
        $this->info('Migrating Friend Requests...');

        // Note: Assumes legacy table is renamed to 'friend_requests_legacy' if 'friend_requests' is the new table
        // Or checks if 'friend_requests' has legacy columns.
        $sourceTable = 'friend_requests';

        if (! Schema::hasTable($sourceTable)) {
            $this->warn("Legacy source table '$sourceTable' not found. Skipping.");

            return;
        }

        DB::table($sourceTable)->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $receiverId = $row->user_id;
                $majors = ['english', 'korea', 'chinese', 'japanese', 'russian'];

                foreach ($majors as $major) {
                    if (! empty($row->$major)) {
                        $requestsArr = json_decode($row->$major, true);
                        if (is_array($requestsArr)) {
                            foreach ($requestsArr as $req) {
                                if (isset($req['my_id'])) {
                                    $senderId = $req['my_id'];
                                    try {
                                        // Use upsert
                                        DB::table('friend_request_lists')->upsert(
                                            [
                                                'sender_id' => $senderId,
                                                'receiver_id' => $receiverId,
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ],
                                            ['sender_id', 'receiver_id'], // Unique constraint
                                            ['updated_at']
                                        );
                                    } catch (\Exception $e) {
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->output->write('.');
        });
        $this->newLine();
        $this->info('Friend Requests migration done.');
    }

    private function migrateUserData()
    {
        $this->info('Migrating User Data...');

        // Map of major prefix to table name
        $tables = [
            'english' => 'ee_user_datas',
            'korea' => 'ko_user_datas',
            'chinese' => 'cn_user_datas',
            'japanese' => 'jp_user_datas',
            'russian' => 'ru_user_datas',
        ];

        foreach ($tables as $major => $tableName) {
            if (! Schema::hasTable($tableName)) {
                $this->warn("Table $tableName not found. Skipping.");

                continue;
            }

            $this->info("Processing $tableName...");

            DB::table($tableName)->orderBy('id')->chunk(100, function ($rows) use ($major) {
                foreach ($rows as $row) {
                    try {
                        // Use upsert
                        DB::table('user_data')->upsert(
                            [
                                'user_id' => $row->phone, // Legacy tables use 'phone' as user ID
                                'major' => $major,
                                'is_vip' => $row->is_vip ?? 0,
                                'diamond_plan' => $row->gold_plan ?? 0,
                                'game_score' => $row->game_score ?? 0,
                                'speaking_level' => $row->speaking_level ?? null, // Specific to some
                                'token' => ! empty($row->token) ? json_encode(['android' => $row->token]) : null,
                                'login_time' => $row->login_time ?? 0,
                                'first_join' => $row->first_join ?? null,
                                'last_active' => $row->last_active ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ],
                            ['user_id', 'major'], // Unique constraint
                            ['is_vip', 'diamond_plan', 'game_score', 'speaking_level', 'token', 'login_time', 'first_join', 'last_active', 'updated_at'] // Update columns
                        );
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
                $this->output->write('.');
            });
            $this->newLine();
        }

        $this->info('User Data migration done.');
    }

    private function migrateWordOfDays()
    {
        $this->info('Migrating Word of Days...');

        $tables = [
            'english' => ['table' => 'wordofday', 'word_col' => 'english'],
            'korea' => ['table' => 'ko_word_of_days', 'word_col' => 'korea'],
        ];

        if (! Schema::hasTable('word_of_days') || ! Schema::hasColumn('word_of_days', 'major')) {
            $this->error('Target table "word_of_days" (with "major" column) not found. Run migrations first.');

            return;
        }

        foreach ($tables as $major => $info) {
            $tableName = $info['table'];
            $wordCol = $info['word_col'];

            if (! Schema::hasTable($tableName)) {
                $this->warn("Table $tableName not found. Skipping.");

                continue;
            }

            if (! Schema::hasColumn($tableName, $wordCol)) {
                $this->warn("Column $wordCol not found in $tableName. Skipping.");

                continue;
            }

            $this->info("Processing $tableName...");

            DB::table($tableName)->orderBy('id')->chunk(100, function ($rows) use ($major, $wordCol) {
                foreach ($rows as $row) {
                    try {
                        DB::table('word_of_days')->updateOrInsert(
                            [
                                'major' => $major,
                                'word' => $row->$wordCol,
                                'translation' => $row->myanmar ?? '',
                            ],
                            [
                                'speech' => $row->speech ?? null,
                                'example' => $row->example ?? null,
                                'thumb' => $row->thumb ?? null,
                                'audio' => $row->audio ?? null,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
                $this->output->write('.');
            });
            $this->newLine();
        }

        $this->info('Word of Days migration done.');
    }

    private function migrateGameWords()
    {
        $this->info('Migrating Game Words...');
        if (! Schema::hasTable('game_words')) {
            $this->error('Target table "game_words" not found. Run migrations first.');

            return;
        }

        $databaseName = DB::getDatabaseName();
        $legacyTables = DB::select(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ?',
            [$databaseName, '%\\_game\\_words']
        );

        if (empty($legacyTables)) {
            $this->warn('No legacy *_game_words tables found. Skipping.');

            return;
        }

        foreach ($legacyTables as $tableInfo) {
            $tableName = $tableInfo->table_name;

            // Skip target table itself
            if ($tableName === 'game_words') {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'display_word') || ! Schema::hasColumn($tableName, 'ans')) {
                $this->warn("Table $tableName does not look like a legacy game words table. Skipping.");

                continue;
            }

            $major = $this->resolveMajorFromGameWordTable($tableName);
            $this->info("Processing $tableName as major \"$major\"...");

            DB::table($tableName)->orderBy('id')->chunk(100, function ($rows) use ($major) {
                foreach ($rows as $row) {
                    try {
                        DB::table('game_words')->insert([
                            'major' => $major,
                            'display_word' => $row->display_word,
                            'display_image' => $row->display_image ?? null,
                            'display_audio' => $row->display_audio ?? null,
                            'category' => $row->category ?? 0,
                            'a' => $row->a ?? null,
                            'b' => $row->b ?? null,
                            'c' => $row->c ?? null,
                            'ans' => $row->ans,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        // Keep going; duplicates are acceptable and source data may include bad rows.
                    }
                }
                $this->output->write('.');
            });
            $this->newLine();
        }

        $this->info('Game Words migration done.');
    }

    private function migrateSpeakingChatbot()
    {
        $this->info('Migrating Speaking Chatbot data...');

        $databaseName = DB::getDatabaseName();
        $contentSaturationTableByMajor = [];

        // 1. Migrate Titles (from *_saturation)
        $saturationTables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE '%_saturation'",
            [$databaseName]
        );

        foreach ($saturationTables as $tableInfo) {
            $tableName = $tableInfo->table_name;
            $major = $this->resolveMajorFromPrefix($tableName, '_saturation');

            $this->info("Processing $tableName for titles (major: \"$major\")...");

            if (Schema::hasColumn($tableName, 'saturation_id') && Schema::hasColumn($tableName, 'title')) {
                $contentSaturationTableByMajor[$major] = $tableName;
            }

            DB::table($tableName)->orderBy('id')->chunk(100, function ($rows) use ($major) {
                foreach ($rows as $row) {
                    try {
                        // Check if it's a content table (has title and saturation_id)
                        if (isset($row->saturation_id) && isset($row->title)) {
                            DB::table('speaking_dialogue_titles')->updateOrInsert(
                                [
                                    'major' => $major,
                                    'title' => $row->title,
                                ],
                                [
                                    'updated_at' => now(),
                                    'created_at' => now(),
                                ]
                            );
                        }
                        // If it's a progress table (has phone and level), we still update user_data
                        elseif (isset($row->phone) && isset($row->level)) {
                            DB::table('user_data')->updateOrInsert(
                                [
                                    'user_id' => $row->phone,
                                    'major' => $major,
                                ],
                                [
                                    'speaking_level' => $row->level ?? 1,
                                    'last_active' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                    } catch (\Exception $e) {
                    }
                }
                $this->output->write('.');
            });
            $this->newLine();
        }

        // 2. Migrate Dialogues (from *_speakingtrainer)
        $trainerTables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE '%_speakingtrainer'",
            [$databaseName]
        );

        foreach ($trainerTables as $tableInfo) {
            $tableName = $tableInfo->table_name;
            $major = $this->resolveMajorFromPrefix($tableName, '_speakingtrainer');

            $this->info("Processing $tableName as major \"$major\"...");

            $titleBySaturationId = [];
            if (isset($contentSaturationTableByMajor[$major])) {
                $titleBySaturationId = $this->buildSpeakingDialogueTitleMap($contentSaturationTableByMajor[$major]);
            }

            DB::table($tableName)->orderBy('id')->chunk(100, function ($rows) use ($major, $titleBySaturationId) {
                foreach ($rows as $row) {
                    try {
                        $titleText = isset($row->saturation_id) && isset($titleBySaturationId[$row->saturation_id])
                            ? $titleBySaturationId[$row->saturation_id]
                            : ('Level '.($row->saturation_id ?? 1));

                        $titleId = DB::table('speaking_dialogue_titles')
                            ->where('major', $major)
                            ->where('title', $titleText)
                            ->value('id');

                        if (! $titleId) {
                            // Create a dummy title if not found to maintain integrity
                            $titleId = DB::table('speaking_dialogue_titles')->insertGetId([
                                'major' => $major,
                                'title' => $titleText,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        DB::table('speaking_dialogues')->updateOrInsert(
                            [
                                'id' => $row->id, // Preserve ID if possible for reference
                            ],
                            [
                                'major' => $major,
                                'speaking_dialogue_title_id' => $titleId,
                                'person_a_text' => $row->person_a ?? '',
                                'person_a_translation' => $row->person_a_mm ?? null,
                                'person_b_text' => $row->person_b ?? '',
                                'person_b_translation' => $row->person_b_mm ?? null,
                                'sort_order' => $row->id, // Default to ID if no sort order
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    } catch (\Exception $e) {
                    }
                }
                $this->output->write('.');
            });
            $this->newLine();
        }

        // 3. Migrate Error Logs (from *_error_speech)
        $errorTables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE '%_error_speech'",
            [$databaseName]
        );

        foreach ($errorTables as $tableInfo) {
            $tableName = $tableInfo->table_name;
            $major = $this->resolveMajorFromPrefix($tableName, '_error_speech');

            $this->info("Processing $tableName as major \"$major\"...");

            DB::table($tableName)->orderBy('id')->chunk(100, function ($rows) use ($major) {
                foreach ($rows as $row) {
                    try {
                        DB::table('speaking_error_logs')->insert([
                            'user_id' => $row->user_id ?? $row->phone,
                            'major' => $major,
                            'dialogue_id' => $row->speech_id,
                            'error_text' => $row->error_speech ?? '',
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ]);
                    } catch (\Exception $e) {
                    }
                }
                $this->output->write('.');
            });
            $this->newLine();
        }

        $this->info('Speaking Chatbot migration done.');
    }

    private function buildSpeakingDialogueTitleMap(string $tableName): array
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'saturation_id') || ! Schema::hasColumn($tableName, 'title')) {
            return [];
        }

        $map = [];
        DB::table($tableName)
            ->select('saturation_id', 'title')
            ->orderBy('saturation_id')
            ->chunk(500, function ($rows) use (&$map) {
                foreach ($rows as $row) {
                    if (isset($row->saturation_id) && isset($row->title)) {
                        $map[$row->saturation_id] = $row->title;
                    }
                }
            });

        return $map;
    }

    private function resolveMajorFromPrefix(string $tableName, string $suffix): string
    {
        $prefix = (string) preg_replace('/'.preg_quote($suffix, '/').'$/', '', $tableName);

        $majorMap = [
            'ee' => 'english',
            'ko' => 'korea',
            'cn' => 'chinese',
            'jp' => 'japanese',
            'ru' => 'russian',
        ];

        return $majorMap[$prefix] ?? $prefix;
    }

    private function resolveMajorFromGameWordTable(string $tableName): string
    {
        $prefix = (string) preg_replace('/_game_words$/', '', $tableName);

        $majorMap = [
            'ee' => 'english',
            'en' => 'english',
            'eng' => 'english',
            'english' => 'english',
            'ek' => 'korea',
            'ko' => 'korea',
            'kr' => 'korea',
            'korean' => 'korea',
            'cn' => 'chinese',
            'ch' => 'chinese',
            'zh' => 'chinese',
            'chinese' => 'chinese',
            'jp' => 'japanese',
            'ja' => 'japanese',
            'japanese' => 'japanese',
            'ru' => 'russian',
            'russian' => 'russian',
        ];

        return $majorMap[$prefix] ?? $prefix;
    }
}
