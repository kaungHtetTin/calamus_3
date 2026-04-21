<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VocabLearningService;
use App\Traits\ApiResponse;
use App\Models\Deck;

class VocabLearningController extends Controller
{
    use ApiResponse;

    protected $vocabService;

    public function __construct(VocabLearningService $vocabService)
    {
        $this->vocabService = $vocabService;
    }

    public function getDecks(Request $request)
    {
        try {
            $major = $request->input('major');
            $languageId = $request->input('languageId');
            $userId = $request->input('userId');

            $decks = $this->vocabService->getDecks($major, $languageId, $userId);

            return $this->successResponse($decks, 200, ['total' => count($decks)]);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function getLanguages()
    {
        try {
            $languages = $this->vocabService->getLanguages();
            return $this->successResponse($languages);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function getVocabProgress(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->user_id;

            $progress = $this->vocabService->getVocabProgressForAllDecks($userId);

            return $this->successResponse($progress);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function getCards(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->user_id;
            
            $deckId = $request->input('deckId');

            if (empty($deckId)) {
                return $this->errorResponse('Missing required parameter: deckId', 400);
            }

            $deck = Deck::find($deckId);
            if (!$deck) {
                return $this->errorResponse('Deck not found', 404);
            }

            $languageId = $deck->language_id;
            $wordCount = $request->input('wordCount', 10);
            
            $data = $this->vocabService->getLearningCards($userId, $wordCount, $languageId, $deckId);

            return $this->successResponse($data);

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function rateWord(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->user_id;
            
            $cardId = $request->input('cardId');
            $quality = $request->input('quality');

            if (empty($cardId) || $quality === null) {
                return $this->errorResponse('Missing required parameters: cardId, quality', 400);
            }

            $result = $this->vocabService->rateWord($userId, $cardId, (int)$quality);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            return $this->successResponse($result['data']);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function skipWord(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->user_id;
            
            $cardId = $request->input('cardId');
            $deckId = $request->input('deckId');
            $reason = $request->input('reason', 'already_know');
            $sessionCardIds = $this->normalizeSessionCardIds($request->input('sessionCardIds', []));

            if (empty($cardId) || empty($deckId)) {
                return $this->errorResponse('Missing required parameters: cardId, deckId', 400);
            }

            $deck = Deck::find($deckId);
            if (!$deck) {
                return $this->errorResponse('Deck not found', 404);
            }

            $result = $this->vocabService->skipWord($userId, $cardId, $deck->language_id, $deckId, $reason, $sessionCardIds);

            return $this->successResponse($result['data']);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    private function normalizeSessionCardIds($value): array
    {
        if (is_array($value)) {
            return array_map('intval', array_filter($value));
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_map('intval', array_filter($decoded));
            }
            $parts = array_filter(array_map('trim', explode(',', $value)));
            return array_map('intval', array_filter($parts));
        }
        return [];
    }
}
