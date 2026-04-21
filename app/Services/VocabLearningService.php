<?php

namespace App\Services;

use App\Models\Deck;
use App\Models\Card;
use App\Models\Language;
use App\Models\UserCardState;
use App\Models\UserLearningProgress;
use App\Models\UserWordSkip;
use Illuminate\Support\Facades\DB;

class VocabLearningService
{
    /**
     * Get all languages
     */
    public function getLanguages()
    {
        return Language::orderBy('id', 'asc')->get();
    }

    /**
     * Get vocabulary progress for all decks that user has started
     */
    public function getVocabProgressForAllDecks($userId)
    {
        // Get all decks where user has started learning (has at least one card in user_card_states)
        $decks = DB::table('decks')
            ->join('languages', 'decks.language_id', '=', 'languages.id')
            ->join('cards', 'cards.deck_id', '=', 'decks.id')
            ->join('user_card_states', 'user_card_states.card_id', '=', 'cards.id')
            ->where('user_card_states.user_id', $userId)
            ->select('decks.id as deck_id', 'decks.title as deck_title', 'decks.language_id', 'languages.name as language_name')
            ->distinct()
            ->orderBy('languages.id', 'asc')
            ->orderBy('decks.id', 'asc')
            ->get();

        if ($decks->isEmpty()) {
            return [];
        }

        $progressData = [];

        foreach ($decks as $deck) {
            $languageId = $deck->language_id;
            
            if (!isset($progressData[$languageId])) {
                $progressData[$languageId] = [
                    'language_id' => $languageId,
                    'language_name' => $deck->language_name,
                    'decks' => []
                ];
            }

            $progressData[$languageId]['decks'][] = array_merge(
                [
                    'deck_id' => $deck->deck_id,
                    'deck_title' => $deck->deck_title,
                ],
                $this->getDeckProgress($userId, $languageId, $deck->deck_id)
            );
        }

        return array_values($progressData);
    }

    /**
     * Get decks with optional progress
     */
    public function getDecks($major, $languageId, $userId = null)
    {
        $query = Deck::query();

        if ($major) {
            $query->where('major', $major);
        } elseif ($languageId) {
            $query->where('language_id', $languageId);
        }

        $decks = $query->orderBy('id', 'asc')->get();

        if ($userId && $decks->isNotEmpty()) {
            foreach ($decks as $deck) {
                $deck->progress = $this->getDeckProgress($userId, $deck->language_id, $deck->id);
            }
        }

        return $decks;
    }

    /**
     * Get deck progress stats
     */
    public function getDeckProgress($userId, $languageId, $deckId)
    {
        $totalCards = Card::where('deck_id', $deckId)->count();
        if ($totalCards == 0) return null;

        $learningDay = $this->getCurrentLearningDay($userId, $languageId, $deckId);

        $masteredCards = DB::table('user_card_states')
            ->join('cards', 'cards.id', '=', 'user_card_states.card_id')
            ->where('user_card_states.user_id', $userId)
            ->where('cards.deck_id', $deckId)
            ->where('user_card_states.due_at', '>', 365)
            ->count();

        $learnedCards = DB::table('user_card_states')
            ->join('cards', 'cards.id', '=', 'user_card_states.card_id')
            ->where('user_card_states.user_id', $userId)
            ->where('cards.deck_id', $deckId)
            ->whereNotNull('user_card_states.due_at')
            ->where('user_card_states.suspended', 0)
            ->where(function ($q) use ($learningDay) {
                $q->whereNull('user_card_states.paused_until')
                  ->orWhere('user_card_states.paused_until', '<=', $learningDay);
            })
            ->count();

        $recallWords = DB::table('user_card_states')
            ->join('cards', 'cards.id', '=', 'user_card_states.card_id')
            ->where('user_card_states.user_id', $userId)
            ->where('cards.deck_id', $deckId)
            ->whereNotNull('user_card_states.due_at')
            ->where('user_card_states.due_at', '<=', $learningDay)
            ->where('user_card_states.suspended', 0)
            ->where(function ($q) use ($learningDay) {
                $q->whereNull('user_card_states.paused_until')
                  ->orWhere('user_card_states.paused_until', '<=', $learningDay);
            })
            ->count();

        $limit = $this->getWordCountForLearningDay($learningDay);
        $availableNew = max(0, $totalCards - $learnedCards);
        $remainingSlots = max(0, $limit - $recallWords);
        $newWords = min($remainingSlots, $availableNew);

        $progressPercent = $totalCards > 0 ? round(($masteredCards / $totalCards) * 100) : 0;

        return [
            'total_cards' => $totalCards,
            'mastered_cards' => $masteredCards,
            'learned_cards' => $learnedCards,
            'recall_words' => $recallWords,
            'new_words' => $newWords,
            'progress_percent' => $progressPercent,
            'current_learning_day' => $learningDay
        ];
    }

    public function getLearningCards($userId, $wordCount, $languageId, $deckId)
    {
        $learningDay = $this->getCurrentLearningDay($userId, $languageId, $deckId);
        $learningDayWordCount = $this->getWordCountForLearningDay($learningDay);
        $actualWordCount = min($wordCount, $learningDayWordCount);

        // Get recall words
        $recallCards = Card::join('user_card_states', 'user_card_states.card_id', '=', 'cards.id')
            ->where('user_card_states.user_id', $userId)
            ->where('cards.deck_id', $deckId)
            ->whereNotNull('user_card_states.due_at')
            ->where('user_card_states.due_at', '<=', $learningDay)
            ->where('user_card_states.suspended', 0)
            ->where(function ($q) use ($learningDay) {
                $q->whereNull('user_card_states.paused_until')
                  ->orWhere('user_card_states.paused_until', '<=', $learningDay);
            })
            ->select('cards.*')
            ->get();

        // Get new words (not in user_card_states or due_at is null)
        $recallCount = $recallCards->count();
        $remainingSlots = max(0, $actualWordCount - $recallCount);
        
        $newCards = collect();
        if ($remainingSlots > 0) {
            // Get IDs already learned/seen/skipped
            $seenIds = UserCardState::where('user_id', $userId)
                ->pluck('card_id');
            
            $skippedIds = $this->getSkippedCardIds($userId, $languageId);
            $excludeIds = $seenIds->merge($skippedIds)->unique();

            $newCards = Card::where('deck_id', $deckId)
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->limit($remainingSlots)
                ->get();
        }

        $allWords = [];
        foreach ($recallCards as $card) {
            $allWords[] = [
                'card' => $card,
                'rich_data' => $this->getRichWordData($card),
                'word_type' => 'recall',
                'is_known' => false
            ];
        }
        foreach ($newCards as $card) {
            $allWords[] = [
                'card' => $card,
                'rich_data' => $this->getRichWordData($card),
                'word_type' => 'new',
                'is_known' => false
            ];
        }

        return [
            'words' => $allWords,
            'step' => 'step1',
            'next_step' => 'step2',
            'learning_day_number' => $learningDay,
            'deck_id' => $deckId,
            'word_counts' => [
                'total' => count($allWords),
                'recall_words' => $recallCount,
                'new_words' => $newCards->count(),
                'requested_count' => $wordCount,
                'learning_day_limit' => $learningDayWordCount,
                'actual_limit' => $actualWordCount
            ],
            'filters' => [
                'language_id' => $languageId,
                'deck_id' => $deckId,
                'excluded_skipped_words' => $this->getSkippedCardIds($userId, $languageId)->count()
            ]
        ];
    }

    public function rateWord($userId, $cardId, $quality)
    {
        $card = Card::find($cardId);
        if (!$card) {
            return ['success' => false, 'message' => 'Card not found'];
        }

        $learningDay = $this->getCurrentLearningDay($userId, $card->language_id, $card->deck_id);
        
        $state = UserCardState::where('user_id', $userId)->where('card_id', $cardId)->first();
        
        $ef = $state ? (float)$state->ef : 2.5;
        $interval = $state ? (int)$state->interval_ : 0;
        $repetitions = $state ? (int)$state->repetitions : 0;

        $sm2 = $this->calculateSM2($ef, $interval, $repetitions, $quality, $learningDay);

        UserCardState::updateOrCreate(
            ['user_id' => $userId, 'card_id' => $cardId],
            [
                'ef' => $sm2['ef'],
                'interval_' => $sm2['interval'],
                'repetitions' => $sm2['repetitions'],
                'due_at' => $sm2['due_at'],
                'suspended' => 0,
                'paused_until' => null
            ]
        );

        return [
            'success' => true,
            'data' => [
                'card_id' => $cardId,
                'quality' => $quality,
                'sm2_result' => [
                    'ef' => $sm2['ef'],
                    'interval' => $sm2['interval'],
                    'repetitions' => $sm2['repetitions'],
                    'next_review_learning_day' => $sm2['due_at'],
                    'current_learning_day' => $learningDay
                ]
            ]
        ];
    }

    public function skipWord($userId, $cardId, $languageId, $deckId, $reason = 'already_know', $sessionCardIds = [])
    {
        // 1. Record the skip
        UserWordSkip::create([
            'user_id' => $userId,
            'card_id' => $cardId,
            'language_id' => $languageId,
            'reason' => $reason,
            'skipped_at' => now()
        ]);

        // 2. Pause the card permanently
        UserCardState::updateOrCreate(
            ['user_id' => $userId, 'card_id' => $cardId],
            [
                'ef' => 2.5,
                'interval_' => 0,
                'repetitions' => 0,
                'due_at' => 999999,
                'suspended' => 0,
                'paused_until' => 999999
            ]
        );

        // 3. Get replacement word
        $replacementWord = $this->getReplacementWord($userId, $languageId, $deckId, $sessionCardIds);

        return [
            'success' => true,
            'data' => [
                'replacement_word' => $replacementWord,
                'skipped_word' => [
                    'card_id' => $cardId,
                    'paused_until' => 'permanent'
                ]
            ]
        ];
    }

    private function getSkippedCardIds($userId, $languageId)
    {
        return UserWordSkip::where('user_id', $userId)
            ->where('language_id', $languageId)
            ->pluck('card_id');
    }

    private function normalizeCardIdsArray($value): array
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

    private function getReplacementWord($userId, $languageId, $deckId, $excludeCardIds = [])
    {
        $excludeCardIds = $this->normalizeCardIdsArray($excludeCardIds);
        $skippedIds = $this->getSkippedCardIds($userId, $languageId);
        $excludeIds = array_unique(array_merge($skippedIds->toArray(), $excludeCardIds));
        
        $card = Card::where('language_id', $languageId)
            ->where('deck_id', $deckId)
            ->whereNotIn('id', $excludeIds)
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('user_card_states')
                    ->whereColumn('user_card_states.card_id', 'cards.id')
                    ->where('user_card_states.user_id', $userId);
            })
            ->inRandomOrder()
            ->first();

        if (!$card) return null;

        return [
            'card' => $card,
            'rich_data' => $this->getRichWordData($card),
            'word_type' => 'new',
            'is_known' => false
        ];
    }

    private function getRichWordData($card)
    {
        // Handle Eloquent models, stdClass, or arrays
        if ($card instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $card->toArray();
        } elseif (is_object($card)) {
            $data = (array)$card;
        } else {
            $data = $card;
        }

        return [
            'word' => $data['word'] ?? null,
            'ipa' => $data['ipa'] ?? null,
            'pronunciation_audio' => $data['pronunciation_audio'] ?? null,
            'parts_of_speech' => !empty($data['parts_of_speech']) ? json_decode($data['parts_of_speech'], true) : null,
            'burmese_translation' => $data['burmese_translation'] ?? null,
            'example_sentences' => !empty($data['example_sentences']) ? json_decode($data['example_sentences'], true) : null,
            'synonyms' => !empty($data['synonyms']) ? json_decode($data['synonyms'], true) : null,
            'antonyms' => !empty($data['antonyms']) ? json_decode($data['antonyms'], true) : null,
            'image' => $data['image'] ?? null
        ];
    }

    private function calculateSM2($ef, $interval, $repetitions, $quality, $currentLearningDay)
    {
        if ($quality < 3) {
            $repetitions = 0;
            $interval = 1;
        } else {
            $ef = $ef + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
            $ef = max(1.3, $ef);
            
            if ($repetitions === 0) {
                $interval = 1;
            } elseif ($repetitions === 1) {
                $interval = 6;
            } else {
                $interval = (int)round($interval * $ef);
            }
            $repetitions++;
        }

        return [
            'ef' => round($ef, 2),
            'interval' => $interval,
            'repetitions' => $repetitions,
            'due_at' => $currentLearningDay + $interval
        ];
    }

    private function getCurrentLearningDay($userId, $languageId, $deckId)
    {
        $progress = UserLearningProgress::where('user_id', $userId)
            ->where('language_id', $languageId)
            ->where('deck_id', $deckId)
            ->first();

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if (!$progress) {
            $progress = UserLearningProgress::create([
                'user_id' => $userId,
                'language_id' => $languageId,
                'deck_id' => $deckId,
                'current_learning_day' => 1,
                'last_session_date' => $today,
                'total_learning_days' => 1,
                'streak_count' => 1,
                'longest_streak' => 1
            ]);
            return 1;
        }

        $lastSessionDate = $progress->last_session_date;
        $currentLearningDay = (int)$progress->current_learning_day;

        // Check if consecutive (learned yesterday)
        if ($lastSessionDate === $yesterday) {
            $newLearningDay = $currentLearningDay + 1;
            $progress->update([
                'current_learning_day' => $newLearningDay,
                'last_session_date' => $today,
                'total_learning_days' => $progress->total_learning_days + 1,
                'streak_count' => $progress->streak_count + 1,
                'longest_streak' => max($progress->longest_streak, $progress->streak_count + 1)
            ]);
            return $newLearningDay;
        } elseif ($lastSessionDate !== $today) {
            // Gap in learning - don't advance learning day, reset streak
            $progress->update([
                'last_session_date' => $today,
                'streak_count' => 1
            ]);
        }
        
        return $currentLearningDay;
    }

    private function getWordCountForLearningDay($day)
    {
        if ($day <= 4) {
            return $day * 5; // 5, 10, 15, 20
        }
        return 20; // From day 5 onwards: 20 words
    }
}
