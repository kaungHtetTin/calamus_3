<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Models\GameWord;
use App\Models\Language;
use App\Models\LibraryBook;
use App\Models\SpeakingDialogue;
use App\Models\SpeakingDialogueTitle;
use App\Models\WordOfDay;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ResourceManagementController extends Controller
{
    private function storeWordOfDayFile(?UploadedFile $file, string $major, string $folder, string $prefix): ?string
    {
        if (!$file) {
            return null;
        }

        $safeMajor = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($major)));
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $prefix . '_' . time() . '_' . uniqid() . ($extension ? ".{$extension}" : '');
        $path = "resources/{$safeMajor}/word-of-day/{$folder}";
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeMiniLibraryFile(?UploadedFile $file, string $major, string $folder, string $prefix): ?string
    {
        if (!$file) {
            return null;
        }

        $safeMajor = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($major)));
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $prefix . '_' . time() . '_' . uniqid() . ($extension ? ".{$extension}" : '');
        $path = "resources/{$safeMajor}/mini-library/{$folder}";
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeGameWordFile(?UploadedFile $file, string $major, string $folder, string $prefix): ?string
    {
        if (!$file) {
            return null;
        }

        $safeMajor = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($major)));
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $prefix . '_' . time() . '_' . uniqid() . ($extension ? ".{$extension}" : '');
        $path = "resources/{$safeMajor}/game-words/{$folder}";
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function getAdminMajorScope(Request $request)
    {
        $admin = $request->user('admin');
        $raw = collect((array) ($admin?->major_scope ?? []))
            ->map(function ($item) {
                return strtolower(trim((string) $item));
            })
            ->filter()
            ->unique()
            ->values();

        if ($raw->contains('*')) {
            return collect(['*']);
        }

        $languageValues = DB::table('languages')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['name', 'code'])
            ->map(function ($row) {
                $value = strtolower(trim((string) ($row->code ?: $row->name)));
                return $value !== '' ? $value : null;
            })
            ->filter()
            ->unique()
            ->values();

        return $raw
            ->filter(function ($value) use ($languageValues) {
                return $languageValues->contains($value);
            })
            ->values();
    }

    private function filterLanguagesByScope($languages, $scope)
    {
        if ($scope->contains('*')) {
            return $languages;
        }

        if ($scope->isEmpty()) {
            return collect();
        }

        $allowed = $scope->all();
        return $languages->filter(function ($row) use ($allowed) {
            $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
            return $code !== '' && in_array($code, $allowed, true);
        })->values();
    }

    public function index(Request $request)
    {
        $languages = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        $languages = $this->filterLanguagesByScope($languages, $scope);

        return Inertia::render('Admin/ResourceManagement', [
            'languages' => $languages,
        ]);
    }

    public function workspace(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $tab = strtolower(trim((string) $request->query('tab', 'word-of-day')));
        $allowedTabs = collect(['word-of-day', 'mini-library', 'speaking-bot', 'game-word', 'flashcard']);
        if (!$allowedTabs->contains($tab)) {
            $tab = 'word-of-day';
        }

        $languages = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        $languages = $this->filterLanguagesByScope($languages, $scope);

        if ($selectedMajor === '' && $languages->count() > 0) {
            $selectedMajor = (string) ($languages->first()->code ?? '');
        }

        if ($selectedMajor !== '') {
            $normalizedSelected = strtolower(trim($selectedMajor));
            if (!$scope->contains('*') && !$languages->contains(function ($row) use ($normalizedSelected) {
                $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
                return $code === $normalizedSelected;
            })) {
                abort(403);
            }
        }

        $selectedLanguage = $languages->firstWhere('code', $selectedMajor);
        $selectedLanguageId = (int) ($selectedLanguage->id ?? 0);

        $wordOfDays = collect();
        if ($selectedMajor !== '' && $tab === 'word-of-day') {
            $wordOfDays = WordOfDay::query()
                ->byMajor($selectedMajor)
                ->orderByDesc('id')
                ->get(['id', 'major', 'word', 'translation', 'speech', 'example', 'thumb', 'audio', 'created_at']);
        }

        $libraryCategories = collect();
        $libraryBooks = collect();
        if ($selectedMajor !== '' && $tab === 'mini-library') {
            $libraryCategories = LibraryBook::query()
                ->where('major', strtolower($selectedMajor))
                ->whereNotNull('category')
                ->where('category', '<>', '')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->values();

            $libraryBooks = LibraryBook::query()
                ->where('major', strtolower($selectedMajor))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'title', 'category', 'pdf_file', 'pdf_url', 'cover_image', 'major', 'created_at']);
        }

        $gameWords = collect();
        if ($selectedMajor !== '' && $tab === 'game-word') {
            $gameWords = GameWord::query()
                ->where('major', strtolower($selectedMajor))
                ->orderByDesc('id')
                ->get(['id', 'major', 'display_word', 'display_image', 'display_audio', 'category', 'a', 'b', 'c', 'ans', 'created_at']);
        }

        $speakingDialogueTitles = collect();
        $speakingDialogues = collect();
        $speakingDialogueTitleId = (int) $request->query('speaking_dialogue_title_id', 0);
        if ($selectedMajor !== '' && $tab === 'speaking-bot') {
            $speakingDialogueTitles = SpeakingDialogueTitle::query()
                ->where('major', strtolower($selectedMajor))
                ->orderByDesc('id')
                ->get(['id', 'major', 'title', 'created_at', 'updated_at']);

            if ($speakingDialogueTitleId <= 0 && $speakingDialogueTitles->count() > 0) {
                $speakingDialogueTitleId = (int) $speakingDialogueTitles->first()->id;
            }

            if ($speakingDialogueTitleId > 0) {
                $validTitle = $speakingDialogueTitles->firstWhere('id', $speakingDialogueTitleId);
                if ($validTitle) {
                    $speakingDialogues = SpeakingDialogue::query()
                        ->where('speaking_dialogue_title_id', $speakingDialogueTitleId)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get([
                            'id',
                            'major',
                            'speaking_dialogue_title_id',
                            'person_a_text',
                            'person_a_translation',
                            'person_b_text',
                            'person_b_translation',
                            'sort_order',
                            'created_at',
                            'updated_at',
                        ]);
                } else {
                    $speakingDialogueTitleId = 0;
                }
            }
        }

        $flashcardDecks = collect();
        $flashcardCards = null;
        $flashcardDeckId = (int) $request->query('deck_id', 0);
        if ($selectedMajor !== '' && $tab === 'flashcard' && $selectedLanguageId > 0) {
            $flashcardDecks = Deck::query()
                ->where('language_id', $selectedLanguageId)
                ->orderByDesc('id')
                ->get(['id', 'title', 'description', 'language_id', 'created_at', 'updated_at']);

            if ($flashcardDeckId <= 0 && $flashcardDecks->count() > 0) {
                $flashcardDeckId = (int) $flashcardDecks->first()->id;
            }

            if ($flashcardDeckId > 0) {
                $validDeck = $flashcardDecks->firstWhere('id', $flashcardDeckId);
                if ($validDeck) {
                    $cardsPerPage = (int) $request->query('cards_per_page', 25);
                    if ($cardsPerPage <= 0) {
                        $cardsPerPage = 25;
                    }
                    if ($cardsPerPage > 200) {
                        $cardsPerPage = 200;
                    }

                    $cardSearch = trim((string) $request->query('card_search', ''));

                    $cardsQuery = Card::query()
                        ->where('language_id', $selectedLanguageId)
                        ->where('deck_id', $flashcardDeckId)
                        ->orderByDesc('id');

                    if ($cardSearch !== '') {
                        $cardsQuery->where(function ($q) use ($cardSearch) {
                            $q->where('word', 'like', '%' . $cardSearch . '%')
                                ->orWhere('burmese_translation', 'like', '%' . $cardSearch . '%')
                                ->orWhere('ipa', 'like', '%' . $cardSearch . '%');
                        });
                    }

                    $flashcardCards = $cardsQuery
                        ->paginate(
                            $cardsPerPage,
                            [
                                'id',
                                'deck_id',
                                'language_id',
                                'word',
                                'ipa',
                                'pronunciation_audio',
                                'parts_of_speech',
                                'burmese_translation',
                                'example_sentences',
                                'synonyms',
                                'antonyms',
                                'relatived',
                                'image',
                                'created_at',
                                'updated_at',
                            ],
                            'cards_page'
                        )
                        ->withQueryString();
                } else {
                    $flashcardDeckId = 0;
                }
            }
        }

        return Inertia::render('Admin/ResourceManagementWorkspace', [
            'languages' => $languages,
            'selectedMajor' => $selectedMajor,
            'selectedLanguage' => $selectedLanguage,
            'tab' => $tab,
            'wordOfDays' => $wordOfDays,
            'libraryCategories' => $libraryCategories,
            'libraryBooks' => $libraryBooks,
            'gameWords' => $gameWords,
            'speakingDialogueTitles' => $speakingDialogueTitles,
            'speakingDialogues' => $speakingDialogues,
            'speakingDialogueTitleId' => $speakingDialogueTitleId,
            'flashcardDecks' => $flashcardDecks,
            'flashcardCards' => $flashcardCards,
            'flashcardDeckId' => $flashcardDeckId,
        ]);
    }

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

    public function storeFlashcardDeck(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0) {
            return redirect()->back()->withErrors(['major' => 'Invalid language scope.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $deck = new Deck();
        $deck->title = trim((string) $data['title']);
        $deck->description = isset($data['description']) ? trim((string) $data['description']) : null;
        $deck->language_id = $languageId;
        $deck->save();

        return redirect()->back()->with('success', 'Flashcard deck created successfully.');
    }

    public function updateFlashcardDeck(Request $request, Deck $deck)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0 || (int) $deck->language_id !== $languageId) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $deck->title = trim((string) $data['title']);
        $deck->description = isset($data['description']) ? trim((string) $data['description']) : null;
        $deck->save();

        return redirect()->back()->with('success', 'Flashcard deck updated successfully.');
    }

    public function destroyFlashcardDeck(Request $request, Deck $deck)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0 || (int) $deck->language_id !== $languageId) {
            abort(403);
        }

        Card::query()->where('deck_id', $deck->id)->delete();
        $deck->delete();

        return redirect()->back()->with('success', 'Flashcard deck deleted successfully.');
    }

    public function storeFlashcardCard(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0) {
            return redirect()->back()->withErrors(['major' => 'Invalid language scope.']);
        }

        $data = $request->validate([
            'deck_id' => ['required', 'integer', 'min:1'],
            'word' => ['required', 'string', 'max:255'],
            'ipa' => ['nullable', 'string', 'max:255'],
            'pronunciation_audio' => ['nullable', 'string', 'max:2048'],
            'parts_of_speech' => ['nullable', 'string'],
            'burmese_translation' => ['nullable', 'string'],
            'example_sentences' => ['nullable', 'string'],
            'synonyms' => ['nullable', 'string'],
            'antonyms' => ['nullable', 'string'],
            'relatived' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
        ]);

        $deckId = (int) $data['deck_id'];
        $deckExists = Deck::query()->where('id', $deckId)->where('language_id', $languageId)->exists();
        if (!$deckExists) {
            return redirect()->back()->withErrors(['deck_id' => 'Invalid deck.']);
        }

        $card = new Card();
        $card->deck_id = $deckId;
        $card->language_id = $languageId;
        $card->word = trim((string) $data['word']);
        $card->ipa = isset($data['ipa']) ? trim((string) $data['ipa']) : null;
        $card->pronunciation_audio = isset($data['pronunciation_audio']) ? trim((string) $data['pronunciation_audio']) : null;
        $card->parts_of_speech = $this->normalizeJsonArrayField($data['parts_of_speech'] ?? null);
        $card->burmese_translation = isset($data['burmese_translation']) ? trim((string) $data['burmese_translation']) : null;
        $card->example_sentences = $this->normalizeJsonArrayField($data['example_sentences'] ?? null);
        $card->synonyms = $this->normalizeJsonArrayField($data['synonyms'] ?? null);
        $card->antonyms = $this->normalizeJsonArrayField($data['antonyms'] ?? null);
        $card->relatived = $this->normalizeJsonArrayField($data['relatived'] ?? null);
        $card->image = isset($data['image']) ? trim((string) $data['image']) : null;
        $card->save();

        return redirect()->back()->with('success', 'Flashcard card created successfully.');
    }

    public function updateFlashcardCard(Request $request, Card $card)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0 || (int) $card->language_id !== $languageId) {
            abort(403);
        }

        $data = $request->validate([
            'deck_id' => ['required', 'integer', 'min:1'],
            'word' => ['required', 'string', 'max:255'],
            'ipa' => ['nullable', 'string', 'max:255'],
            'pronunciation_audio' => ['nullable', 'string', 'max:2048'],
            'parts_of_speech' => ['nullable', 'string'],
            'burmese_translation' => ['nullable', 'string'],
            'example_sentences' => ['nullable', 'string'],
            'synonyms' => ['nullable', 'string'],
            'antonyms' => ['nullable', 'string'],
            'relatived' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
        ]);

        $deckId = (int) $data['deck_id'];
        $deckExists = Deck::query()->where('id', $deckId)->where('language_id', $languageId)->exists();
        if (!$deckExists) {
            return redirect()->back()->withErrors(['deck_id' => 'Invalid deck.']);
        }

        $card->deck_id = $deckId;
        $card->word = trim((string) $data['word']);
        $card->ipa = isset($data['ipa']) ? trim((string) $data['ipa']) : null;
        $card->pronunciation_audio = isset($data['pronunciation_audio']) ? trim((string) $data['pronunciation_audio']) : null;
        $card->parts_of_speech = $this->normalizeJsonArrayField($data['parts_of_speech'] ?? null);
        $card->burmese_translation = isset($data['burmese_translation']) ? trim((string) $data['burmese_translation']) : null;
        $card->example_sentences = $this->normalizeJsonArrayField($data['example_sentences'] ?? null);
        $card->synonyms = $this->normalizeJsonArrayField($data['synonyms'] ?? null);
        $card->antonyms = $this->normalizeJsonArrayField($data['antonyms'] ?? null);
        $card->relatived = $this->normalizeJsonArrayField($data['relatived'] ?? null);
        $card->image = isset($data['image']) ? trim((string) $data['image']) : null;
        $card->save();

        return redirect()->back()->with('success', 'Flashcard card updated successfully.');
    }

    public function destroyFlashcardCard(Request $request, Card $card)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0 || (int) $card->language_id !== $languageId) {
            abort(403);
        }

        $card->delete();

        return redirect()->back()->with('success', 'Flashcard card deleted successfully.');
    }

    public function bulkUploadFlashcardCards(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $languageId = (int) Language::query()->where('code', $selectedMajor)->value('id');
        if ($languageId <= 0) {
            return redirect()->back()->withErrors(['major' => 'Invalid language scope.']);
        }

        $data = $request->validate([
            'deck_id' => ['required', 'integer', 'min:1'],
            'cards_json' => ['nullable', 'string'],
            'cards_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $deckId = (int) $data['deck_id'];
        $deckExists = Deck::query()->where('id', $deckId)->where('language_id', $languageId)->exists();
        if (!$deckExists) {
            return redirect()->back()->withErrors(['deck_id' => 'Invalid deck.']);
        }

        $rawJson = trim((string) ($data['cards_json'] ?? ''));
        $file = $request->file('cards_file');
        if ($file) {
            $contents = @file_get_contents($file->getRealPath());
            $rawJson = is_string($contents) ? trim($contents) : '';
        }

        if ($rawJson === '') {
            return redirect()->back()->withErrors(['cards_json' => 'JSON is required.']);
        }

        $decoded = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->back()->withErrors(['cards_json' => 'Invalid JSON format.']);
        }

        $items = [];
        if (is_array($decoded) && array_key_exists('cards', $decoded) && is_array($decoded['cards'])) {
            $items = $decoded['cards'];
        } elseif (is_array($decoded)) {
            $items = $decoded;
        }

        if (!is_array($items) || empty($items)) {
            return redirect()->back()->withErrors(['cards_json' => 'No cards found in JSON.']);
        }

        $now = now();
        $rows = [];
        $skipped = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                $skipped++;
                continue;
            }
            $word = trim((string) ($item['word'] ?? ''));
            if ($word === '') {
                $skipped++;
                continue;
            }

            $rows[] = [
                'deck_id' => $deckId,
                'language_id' => $languageId,
                'word' => $word,
                'ipa' => isset($item['ipa']) ? trim((string) $item['ipa']) : null,
                'pronunciation_audio' => isset($item['pronunciation_audio']) ? trim((string) $item['pronunciation_audio']) : null,
                'parts_of_speech' => $this->normalizeJsonArrayField($item['parts_of_speech'] ?? null),
                'burmese_translation' => isset($item['burmese_translation']) ? trim((string) $item['burmese_translation']) : null,
                'example_sentences' => $this->normalizeJsonArrayField($item['example_sentences'] ?? null),
                'synonyms' => $this->normalizeJsonArrayField($item['synonyms'] ?? null),
                'antonyms' => $this->normalizeJsonArrayField($item['antonyms'] ?? null),
                'relatived' => $this->normalizeJsonArrayField($item['relatived'] ?? null),
                'image' => isset($item['image']) ? trim((string) $item['image']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return redirect()->back()->withErrors(['cards_json' => 'No valid cards to upload.']);
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                Card::query()->insert($chunk);
            }
        });

        $inserted = count($rows);
        $message = $skipped > 0 ? "Uploaded {$inserted} cards. Skipped {$skipped} invalid items." : "Uploaded {$inserted} cards.";

        return redirect()->back()->with('success', $message);
    }

    public function storeWordOfDay(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'word' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'speech' => ['nullable', 'string', 'max:100'],
            'example' => ['nullable', 'string'],
            'thumb_file' => ['nullable', 'image', 'max:4096'],
            'audio_file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:20480'],
        ]);

        $thumbUrl = $this->storeWordOfDayFile($request->file('thumb_file'), $selectedMajor, 'thumbs', 'thumb');
        $audioUrl = $this->storeWordOfDayFile($request->file('audio_file'), $selectedMajor, 'audio', 'audio');

        WordOfDay::create([
            'major' => strtolower($selectedMajor),
            'word' => trim($data['word']),
            'translation' => trim($data['translation']),
            'speech' => isset($data['speech']) ? trim((string) $data['speech']) : null,
            'example' => isset($data['example']) ? trim((string) $data['example']) : null,
            'thumb' => $thumbUrl,
            'audio' => $audioUrl,
        ]);

        return redirect()->back()->with('success', 'Word of the day created successfully.');
    }

    public function updateWordOfDay(Request $request, WordOfDay $wordOfDay)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $wordOfDay->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $data = $request->validate([
            'word' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'speech' => ['nullable', 'string', 'max:100'],
            'example' => ['nullable', 'string'],
            'thumb_file' => ['nullable', 'image', 'max:4096'],
            'audio_file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:20480'],
        ]);

        $thumbUrl = $wordOfDay->thumb;
        $audioUrl = $wordOfDay->audio;

        $newThumb = $this->storeWordOfDayFile($request->file('thumb_file'), $selectedMajor, 'thumbs', 'thumb');
        $newAudio = $this->storeWordOfDayFile($request->file('audio_file'), $selectedMajor, 'audio', 'audio');

        if ($newThumb) {
            $thumbUrl = $newThumb;
        }
        if ($newAudio) {
            $audioUrl = $newAudio;
        }

        $wordOfDay->update([
            'word' => trim($data['word']),
            'translation' => trim($data['translation']),
            'speech' => isset($data['speech']) ? trim((string) $data['speech']) : null,
            'example' => isset($data['example']) ? trim((string) $data['example']) : null,
            'thumb' => $thumbUrl,
            'audio' => $audioUrl,
        ]);

        return redirect()->back()->with('success', 'Word of the day updated successfully.');
    }

    public function destroyWordOfDay(Request $request, WordOfDay $wordOfDay)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $wordOfDay->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $wordOfDay->delete();

        return redirect()->back()->with('success', 'Word of the day deleted successfully.');
    }

    public function storeLibraryBook(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'cover_file' => ['nullable', 'image', 'max:4096'],
        ]);

        $pdfUrl = $this->storeMiniLibraryFile($request->file('pdf_file'), $selectedMajor, 'pdfs', 'pdf');
        $coverUrl = $this->storeMiniLibraryFile($request->file('cover_file'), $selectedMajor, 'covers', 'cover');

        $pdfFilePath = $pdfUrl ? parse_url($pdfUrl, PHP_URL_PATH) : null;
        $pdfFilePath = is_string($pdfFilePath) ? ltrim((string) $pdfFilePath, '/') : null;

        LibraryBook::create([
            'title' => trim($data['title']),
            'category' => trim($data['category']),
            'major' => strtolower($selectedMajor),
            'pdf_file' => $pdfFilePath,
            'pdf_url' => $pdfUrl,
            'cover_image' => $coverUrl,
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Library book created successfully.');
    }

    public function updateLibraryBook(Request $request, LibraryBook $libraryBook)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $libraryBook->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
            'cover_file' => ['nullable', 'image', 'max:4096'],
        ]);

        $pdfUrl = $libraryBook->pdf_url;
        $pdfFilePath = $libraryBook->pdf_file;
        $coverUrl = $libraryBook->cover_image;

        $newPdfUrl = $this->storeMiniLibraryFile($request->file('pdf_file'), $selectedMajor, 'pdfs', 'pdf');
        if ($newPdfUrl) {
            $pdfUrl = $newPdfUrl;
            $path = parse_url($newPdfUrl, PHP_URL_PATH);
            $pdfFilePath = is_string($path) ? ltrim((string) $path, '/') : $pdfFilePath;
        }

        $newCoverUrl = $this->storeMiniLibraryFile($request->file('cover_file'), $selectedMajor, 'covers', 'cover');
        if ($newCoverUrl) {
            $coverUrl = $newCoverUrl;
        }

        $libraryBook->update([
            'title' => trim($data['title']),
            'category' => trim($data['category']),
            'pdf_file' => $pdfFilePath,
            'pdf_url' => $pdfUrl,
            'cover_image' => $coverUrl,
        ]);

        return redirect()->back()->with('success', 'Library book updated successfully.');
    }

    public function destroyLibraryBook(Request $request, LibraryBook $libraryBook)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $libraryBook->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $libraryBook->delete();

        return redirect()->back()->with('success', 'Library book deleted successfully.');
    }

    public function storeGameWord(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'type' => ['required', 'in:word,image,audio'],
            'display_word' => ['required', 'string', 'max:255'],
            'a' => ['required', 'string', 'max:255'],
            'b' => ['required', 'string', 'max:255'],
            'c' => ['required', 'string', 'max:255'],
            'ans' => ['required', 'in:a,b,c'],
            'display_image_file' => ['nullable', 'image', 'max:4096'],
            'display_audio_file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:20480'],
        ]);

        $type = (string) $data['type'];
        $category = $type === 'word' ? 1 : ($type === 'image' ? 2 : 3);

        $imageUrl = null;
        $audioUrl = null;
        if ($type === 'image') {
            $imageUrl = $this->storeGameWordFile($request->file('display_image_file'), $selectedMajor, 'images', 'image');
            if (!$imageUrl) {
                return redirect()->back()->withErrors(['display_image_file' => 'Image file is required.']);
            }
        }
        if ($type === 'audio') {
            $audioUrl = $this->storeGameWordFile($request->file('display_audio_file'), $selectedMajor, 'audio', 'audio');
            if (!$audioUrl) {
                return redirect()->back()->withErrors(['display_audio_file' => 'Audio file is required.']);
            }
        }

        GameWord::create([
            'major' => strtolower($selectedMajor),
            'display_word' => trim((string) $data['display_word']),
            'display_image' => $imageUrl,
            'display_audio' => $audioUrl,
            'category' => $category,
            'a' => trim((string) $data['a']),
            'b' => trim((string) $data['b']),
            'c' => trim((string) $data['c']),
            'ans' => (string) $data['ans'],
        ]);

        return redirect()->back()->with('success', 'Game word created successfully.');
    }

    public function updateGameWord(Request $request, GameWord $gameWord)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $gameWord->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $data = $request->validate([
            'type' => ['required', 'in:word,image,audio'],
            'display_word' => ['required', 'string', 'max:255'],
            'a' => ['required', 'string', 'max:255'],
            'b' => ['required', 'string', 'max:255'],
            'c' => ['required', 'string', 'max:255'],
            'ans' => ['required', 'in:a,b,c'],
            'display_image_file' => ['nullable', 'image', 'max:4096'],
            'display_audio_file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:20480'],
        ]);

        $type = (string) $data['type'];
        $category = $type === 'word' ? 1 : ($type === 'image' ? 2 : 3);

        $imageUrl = $gameWord->display_image;
        $audioUrl = $gameWord->display_audio;

        if ($type === 'image') {
            $newImageUrl = $this->storeGameWordFile($request->file('display_image_file'), $selectedMajor, 'images', 'image');
            if ($newImageUrl) {
                $imageUrl = $newImageUrl;
            }
            $audioUrl = null;
        } elseif ($type === 'audio') {
            $newAudioUrl = $this->storeGameWordFile($request->file('display_audio_file'), $selectedMajor, 'audio', 'audio');
            if ($newAudioUrl) {
                $audioUrl = $newAudioUrl;
            }
            $imageUrl = null;
        } else {
            $imageUrl = null;
            $audioUrl = null;
        }

        $gameWord->update([
            'display_word' => trim((string) $data['display_word']),
            'display_image' => $imageUrl,
            'display_audio' => $audioUrl,
            'category' => $category,
            'a' => trim((string) $data['a']),
            'b' => trim((string) $data['b']),
            'c' => trim((string) $data['c']),
            'ans' => (string) $data['ans'],
        ]);

        return redirect()->back()->with('success', 'Game word updated successfully.');
    }

    public function destroyGameWord(Request $request, GameWord $gameWord)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $gameWord->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $gameWord->delete();

        return redirect()->back()->with('success', 'Game word deleted successfully.');
    }

    public function storeSpeakingDialogueTitle(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        SpeakingDialogueTitle::create([
            'major' => strtolower($selectedMajor),
            'title' => trim((string) $data['title']),
        ]);

        return redirect()->back()->with('success', 'Dialogue title created successfully.');
    }

    public function updateSpeakingDialogueTitle(Request $request, SpeakingDialogueTitle $speakingDialogueTitle)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $speakingDialogueTitle->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $speakingDialogueTitle->update([
            'title' => trim((string) $data['title']),
        ]);

        return redirect()->back()->with('success', 'Dialogue title updated successfully.');
    }

    public function destroySpeakingDialogueTitle(Request $request, SpeakingDialogueTitle $speakingDialogueTitle)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $speakingDialogueTitle->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        SpeakingDialogue::query()->where('speaking_dialogue_title_id', $speakingDialogueTitle->id)->delete();
        $speakingDialogueTitle->delete();

        return redirect()->back()->with('success', 'Dialogue title deleted successfully.');
    }

    public function storeSpeakingDialogue(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'speaking_dialogue_title_id' => ['required', 'integer', 'min:1'],
            'person_a_text' => ['required', 'string'],
            'person_a_translation' => ['nullable', 'string'],
            'person_b_text' => ['required', 'string'],
            'person_b_translation' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $titleId = (int) $data['speaking_dialogue_title_id'];
        $titleExists = SpeakingDialogueTitle::query()
            ->where('id', $titleId)
            ->where('major', strtolower($selectedMajor))
            ->exists();

        if (!$titleExists) {
            return redirect()->back()->withErrors(['speaking_dialogue_title_id' => 'Invalid dialogue title.']);
        }

        SpeakingDialogue::create([
            'major' => strtolower($selectedMajor),
            'speaking_dialogue_title_id' => $titleId,
            'person_a_text' => trim((string) $data['person_a_text']),
            'person_a_translation' => isset($data['person_a_translation']) ? trim((string) $data['person_a_translation']) : null,
            'person_b_text' => trim((string) $data['person_b_text']),
            'person_b_translation' => isset($data['person_b_translation']) ? trim((string) $data['person_b_translation']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->back()->with('success', 'Dialogue created successfully.');
    }

    public function updateSpeakingDialogue(Request $request, SpeakingDialogue $speakingDialogue)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $speakingDialogue->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $data = $request->validate([
            'speaking_dialogue_title_id' => ['required', 'integer', 'min:1'],
            'person_a_text' => ['required', 'string'],
            'person_a_translation' => ['nullable', 'string'],
            'person_b_text' => ['required', 'string'],
            'person_b_translation' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $titleId = (int) $data['speaking_dialogue_title_id'];
        $titleExists = SpeakingDialogueTitle::query()
            ->where('id', $titleId)
            ->where('major', strtolower($selectedMajor))
            ->exists();

        if (!$titleExists) {
            return redirect()->back()->withErrors(['speaking_dialogue_title_id' => 'Invalid dialogue title.']);
        }

        $speakingDialogue->update([
            'speaking_dialogue_title_id' => $titleId,
            'person_a_text' => trim((string) $data['person_a_text']),
            'person_a_translation' => isset($data['person_a_translation']) ? trim((string) $data['person_a_translation']) : null,
            'person_b_text' => trim((string) $data['person_b_text']),
            'person_b_translation' => isset($data['person_b_translation']) ? trim((string) $data['person_b_translation']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->back()->with('success', 'Dialogue updated successfully.');
    }

    public function destroySpeakingDialogue(Request $request, SpeakingDialogue $speakingDialogue)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        if (strtolower(trim((string) $speakingDialogue->major)) !== strtolower($selectedMajor)) {
            abort(403);
        }

        $speakingDialogue->delete();

        return redirect()->back()->with('success', 'Dialogue deleted successfully.');
    }
}
