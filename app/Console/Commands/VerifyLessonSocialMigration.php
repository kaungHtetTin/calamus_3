<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyLessonSocialMigration extends Command
{
    protected $signature = 'legacy:verify-lesson-social';

    protected $description = 'Verify lesson social migration integrity and polymorphic comment targets';

    public function handle(): int
    {
        if (!Schema::hasTable('lessons') || !Schema::hasTable('comment')) {
            $this->error('Required tables not found: lessons/comment');
            return self::FAILURE;
        }

        $this->info('Verifying lesson social migration...');

        $invalidTargets = DB::table('comment')
            ->whereNull('target_type')
            ->orWhereNull('target_id')
            ->orWhereNotIn('target_type', ['post', 'lesson'])
            ->count();

        $this->line("Invalid comment targets: {$invalidTargets}");

        $lessonComments = DB::table('comment')
            ->select('target_id', DB::raw('COUNT(*) as total'))
            ->where('target_type', 'lesson')
            ->groupBy('target_id')
            ->pluck('total', 'target_id');

        $countMismatch = 0;
        foreach ($lessonComments as $lessonId => $count) {
            $stored = (int)DB::table('lessons')->where('id', (int)$lessonId)->value('comment_count');
            if ((int)$count !== $stored) {
                $countMismatch++;
            }
        }

        $this->line("Lesson comment_count mismatches: {$countMismatch}");

        $likeMismatch = 0;
        if (Schema::hasTable('lesson_likes')) {
            $lessonLikes = DB::table('lesson_likes')
                ->select('lesson_id', DB::raw('COUNT(*) as total'))
                ->groupBy('lesson_id')
                ->pluck('total', 'lesson_id');

            foreach ($lessonLikes as $lessonId => $count) {
                $stored = (int)DB::table('lessons')->where('id', (int)$lessonId)->value('like_count');
                if ((int)$count !== $stored) {
                    $likeMismatch++;
                }
            }
        }

        $this->line("Lesson like_count mismatches: {$likeMismatch}");

        $legacyLinkedLessons = DB::table('lessons')
            ->whereNotNull('date')
            ->where('date', '>', 0)
            ->count();
        $this->line("Lessons with legacy post link (date): {$legacyLinkedLessons}");

        if ($invalidTargets > 0 || $countMismatch > 0 || $likeMismatch > 0) {
            $this->warn('Verification completed with issues.');
            return self::FAILURE;
        }

        $this->info('Verification passed.');
        return self::SUCCESS;
    }
}

