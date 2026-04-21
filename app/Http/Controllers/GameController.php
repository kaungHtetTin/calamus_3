<?php

namespace App\Http\Controllers;

use App\Models\GameWord;
use App\Models\Learner;
use App\Models\UserData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    use ApiResponse;

    public function getWord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'major' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $major = strtolower(trim((string) $request->input('major')));

        $word = GameWord::where('major', $major)
            ->inRandomOrder()
            ->first();

        if (!$word) {
            return $this->errorResponse('No game words found for the selected major', 404);
        }

        return $this->successResponse([
            'id' => (int) $word->id,
            'major' => $word->major,
            'display_word' => $word->display_word,
            'display_image' => $word->display_image,
            'display_audio' => $word->display_audio,
            'category' => (int) $word->category,
            'a' => $word->a,
            'b' => $word->b,
            'c' => $word->c,
            'ans' => $word->ans,
        ]);
    }

    public function getTopScores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'major' => 'required|string|max:20',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $major = strtolower(trim((string) $request->input('major')));
        $limit = (int) $request->input('limit', 50);

        $rows = UserData::query()
            ->join('learners', 'learners.user_id', '=', 'user_data.user_id')
            ->where('user_data.major', $major)
            ->where('user_data.game_score', '>', 0)
            ->orderByDesc('user_data.game_score')
            ->orderBy('learners.user_id')
            ->limit($limit)
            ->get([
                'learners.user_id',
                'learners.learner_name',
                'learners.learner_image',
                'user_data.game_score',
            ]);

        $result = $rows->map(function ($row) {
            return [
                'user_id' => (string) $row->user_id,
                'learner_name' => $row->learner_name,
                'learner_image' => $row->learner_image,
                'game_score' => (int) $row->game_score,
            ];
        })->values()->all();

        return $this->successResponse($result);
    }

    public function updateScore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'major' => 'required|string|max:20',
            'score' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $userId = (string) $request->input('user_id');
        $major = strtolower(trim((string) $request->input('major')));
        $score = (int) $request->input('score');

        $learnerExists = Learner::where('user_id', $userId)->exists();
        if (!$learnerExists) {
            return $this->errorResponse('User not found', 404);
        }

        $row = UserData::firstOrCreate(
            [
                'user_id' => $userId,
                'major' => $major,
            ],
            [
                'is_vip' => 0,
                'diamond_plan' => 0,
                'game_score' => 0,
                'login_time' => 0,
            ]
        );

        $updated = false;
        if ($score > (int) $row->game_score) {
            $row->game_score = $score;
            $row->save();
            $updated = true;
        }

        return $this->successResponse([
            'user_id' => $userId,
            'major' => $major,
            'submitted_score' => $score,
            'high_score' => (int) $row->game_score,
            'updated' => $updated,
        ]);
    }
}
