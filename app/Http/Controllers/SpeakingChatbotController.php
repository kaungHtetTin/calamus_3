<?php

namespace App\Http\Controllers;

use App\Models\SpeakingDialogue;
use App\Models\SpeakingDialogueTitle;
use App\Models\SpeakingErrorLog;
use App\Models\UserData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpeakingChatbotController extends Controller
{
    use ApiResponse;

    /**
     * Fetch dialogues for a specific major and level.
     */
    public function getDialogues(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'major' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $userId = $request->user()->user_id;
        $major = $request->input('major');

        $userData = UserData::where('user_id', $userId)
            ->where('major', $major)
            ->first();

        $title = $this->resolveCurrentTitle($userData, $major);

        if (! $title) {
            return $this->errorResponse('Dialogue level not found', 404);
        }

        // Initialize/Update meta if needed
        if ($userData) {
            $meta = $userData->meta ?? [];
            if (! isset($meta['speaking_dialogue_title_id']) || $meta['speaking_dialogue_title_id'] != $title->id) {
                $meta['speaking_dialogue_title_id'] = $title->id;
                $userData->update(['meta' => $meta]);
            }
        }

        $dialogues = SpeakingDialogue::where('speaking_dialogue_title_id', $title->id)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse([
            'level' => $this->resolveLevelForTitle($title),
            'title' => $title->title,
            'dialogues' => $dialogues,
        ]);
    }

    private function resolveLevelForTitle(SpeakingDialogueTitle $title): int
    {
        $major = (string) $title->major;
        if ($major === '') {
            return 1;
        }

        $level = SpeakingDialogueTitle::query()
            ->where('major', $major)
            ->where('id', '<=', $title->id)
            ->count();

        return max(1, (int) $level);
    }

    /**
     * Helper to resolve current title based on meta or legacy level.
     */
    private function resolveCurrentTitle($userData, string $major)
    {
        $titleId = $userData && isset($userData->meta['speaking_dialogue_title_id'])
            ? $userData->meta['speaking_dialogue_title_id']
            : null;

        if ($titleId) {
            $title = SpeakingDialogueTitle::query()
                ->where('id', $titleId)
                ->where('major', $major)
                ->first();

            if ($title) {
                return $title;
            }
        }

        $level = $userData ? (int) ($userData->speaking_level ?? 1) : 1;
        $level = max(1, $level);

        $title = SpeakingDialogueTitle::query()
            ->where('major', $major)
            ->orderBy('id')
            ->skip($level - 1)
            ->first();

        if ($title) {
            return $title;
        }

        return SpeakingDialogueTitle::query()
            ->where('major', $major)
            ->orderBy('id')
            ->first();
    }

    /**
     * Record an error speech attempt.
     */
    public function recordErrorLog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'major' => 'required|string|max:20',
            'dialogue_id' => 'required|exists:speaking_dialogues,id',
            'error_text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $log = SpeakingErrorLog::create([
            'user_id' => $request->user()->user_id,
            'major' => $request->input('major'),
            'dialogue_id' => $request->input('dialogue_id'),
            'error_text' => $request->input('error_text'),
        ]);

        return $this->successResponse($log);
    }

    /**
     * Update user's speaking level progress.
     */
    public function completeLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'major' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $userId = $request->user()->user_id;
        $major = $request->input('major');

        $userData = UserData::where('user_id', $userId)
            ->where('major', $major)
            ->first();

        $currentTitle = $this->resolveCurrentTitle($userData, $major);

        if (! $currentTitle) {
            return $this->errorResponse('Current dialogue level not found', 404);
        }

        // Find the next level/title
        $currentLevel = $this->resolveLevelForTitle($currentTitle);
        $nextTitle = SpeakingDialogueTitle::query()
            ->where('major', $major)
            ->where('id', '>', $currentTitle->id)
            ->orderBy('id')
            ->first();

        $meta = $userData ? ($userData->meta ?? []) : [];
        if ($nextTitle) {
            $meta['speaking_dialogue_title_id'] = $nextTitle->id;
            $newLevel = $currentLevel + 1;
        } else {
            // Stay on current level (max level reached)
            $meta['speaking_dialogue_title_id'] = $currentTitle->id;
            $newLevel = $currentLevel;
        }

        // Update UserData's speaking_level and meta
        $userData = UserData::updateOrCreate(
            ['user_id' => $userId, 'major' => $major],
            [
                'speaking_level' => $newLevel,
                'meta' => $meta,
                'last_active' => now(),
            ]
        );

        return $this->successResponse([
            'major' => $major,
            'current_level' => $newLevel,
            'level_title' => $nextTitle ? $nextTitle->title : $currentTitle->title,
            'is_max_level' => ! $nextTitle,
        ]);
    }

    /**
     * Get user's current speaking progress.
     */
    public function getProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'major' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $userId = $request->user()->user_id;
        $major = $request->input('major');

        $userData = UserData::where('user_id', $userId)
            ->where('major', $major)
            ->first();

        $title = $this->resolveCurrentTitle($userData, $major);

        return $this->successResponse([
            'user_id' => $userId,
            'major' => $major,
            'current_level' => $title ? $this->resolveLevelForTitle($title) : 1,
            'level_title' => $title ? $title->title : 'Level 1',
        ]);
    }
}
