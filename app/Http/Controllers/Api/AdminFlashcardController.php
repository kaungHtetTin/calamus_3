<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Language;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFlashcardController extends Controller
{
    use ApiResponse;

    private function normalizeJsonArrayField($value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        $items = collect(preg_split('/[\r\n,]+/', $raw))
            ->map(function ($item) {
                return trim((string) $item);
            })
            ->filter()
            ->values()
            ->all();

        return empty($items) ? null : json_encode($items, JSON_UNESCAPED_UNICODE);
    }

    private function getItemValue(array $item, string $snakeKey, ?string $camelKey = null)
    {
        if (array_key_exists($snakeKey, $item)) {
            return $item[$snakeKey];
        }
        if ($camelKey && array_key_exists($camelKey, $item)) {
            return $item[$camelKey];
        }
        return null;
    }

    public function bulkUploadCards(Request $request)
    {
        $actor = $request->user();
        if (!$actor instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        $major = strtolower(trim((string) $request->input('major', '')));
        $languageId = (int) $request->input('languageId', 0);

        $language = null;
        if ($languageId > 0) {
            $language = Language::query()->find($languageId);
            if ($language) {
                $major = strtolower(trim((string) ($language->code ?: $language->name ?: '')));
            }
        } elseif ($major !== '') {
            $language = Language::query()->where('code', $major)->first();
        }

        if (!$language || $major === '') {
            return $this->errorResponse('Invalid language (major/languageId).', 400);
        }

        if (!$actor->hasPermission('administration', $major) && !$actor->hasPermission('course', $major)) {
            return $this->errorResponse('Forbidden', 403);
        }

        $deckId = (int) $request->input('deckId', 0);
        $deckTitle = trim((string) $request->input('deckTitle', ''));
        $deckDescription = trim((string) $request->input('deckDescription', ''));

        $deckCreated = false;
        if ($deckId <= 0) {
            if ($deckTitle === '') {
                return $this->errorResponse('deckId or deckTitle is required.', 400);
            }
            $deck = new Deck();
            $deck->title = $deckTitle;
            $deck->description = $deckDescription !== '' ? $deckDescription : null;
            $deck->language_id = (int) $language->id;
            $deck->save();
            $deckId = (int) $deck->id;
            $deckCreated = true;
        } else {
            $deckOk = Deck::query()->where('id', $deckId)->where('language_id', (int) $language->id)->exists();
            if (!$deckOk) {
                return $this->errorResponse('Invalid deckId for this language.', 400);
            }
        }

        $cards = $request->input('cards', null);
        if (is_string($cards)) {
            $decoded = json_decode($cards, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $cards = $decoded;
            }
        }

        if (!is_array($cards)) {
            return $this->errorResponse('cards must be an array.', 422);
        }

        $now = now();
        $rows = [];
        $skipped = 0;

        foreach ($cards as $item) {
            if (!is_array($item)) {
                $skipped++;
                continue;
            }

            $word = trim((string) $this->getItemValue($item, 'word', 'word'));
            if ($word === '') {
                $skipped++;
                continue;
            }

            $rows[] = [
                'deck_id' => $deckId,
                'language_id' => (int) $language->id,
                'word' => $word,
                'ipa' => ($v = $this->getItemValue($item, 'ipa', 'ipa')) !== null ? trim((string) $v) : null,
                'pronunciation_audio' => ($v = $this->getItemValue($item, 'pronunciation_audio', 'pronunciationAudio')) !== null ? trim((string) $v) : null,
                'parts_of_speech' => $this->normalizeJsonArrayField($this->getItemValue($item, 'parts_of_speech', 'partsOfSpeech')),
                'burmese_translation' => ($v = $this->getItemValue($item, 'burmese_translation', 'burmeseTranslation')) !== null ? trim((string) $v) : null,
                'example_sentences' => $this->normalizeJsonArrayField($this->getItemValue($item, 'example_sentences', 'exampleSentences')),
                'synonyms' => $this->normalizeJsonArrayField($this->getItemValue($item, 'synonyms', 'synonyms')),
                'antonyms' => $this->normalizeJsonArrayField($this->getItemValue($item, 'antonyms', 'antonyms')),
                'relatived' => $this->normalizeJsonArrayField($this->getItemValue($item, 'relatived', 'relatived')),
                'image' => ($v = $this->getItemValue($item, 'image', 'image')) !== null ? trim((string) $v) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return $this->errorResponse('No valid cards to upload.', 422);
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                Card::query()->insert($chunk);
            }
        });

        return $this->successResponse([
            'deck_id' => $deckId,
            'deck_created' => $deckCreated,
            'inserted' => count($rows),
            'skipped' => $skipped,
        ], 201);
    }
}

