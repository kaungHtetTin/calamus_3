<?php

namespace App\Services;

use App\Models\WordOfDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WordOfDayService
{
    public function getDailyWord(string $major, ?string $timezone = null): array
    {
        $normalizedMajor = $this->normalizeMajor($major);
        $resolvedTimezone = $this->resolveTimezone($timezone);
        $localDate = $this->resolveLocalDate($resolvedTimezone);
        $cacheKey = $this->cacheKey($normalizedMajor, $localDate, $resolvedTimezone);
        $ttl = $this->ttlUntilNextMidnight($resolvedTimezone);

        try {
            return Cache::remember($cacheKey, $ttl, function () use ($normalizedMajor, $localDate, $resolvedTimezone) {
                return $this->selectDailyWord($normalizedMajor, $localDate, $resolvedTimezone);
            });
        } catch (Throwable $e) {
            // If cache backend is temporarily unavailable, still serve the daily word.
            return $this->selectDailyWord($normalizedMajor, $localDate, $resolvedTimezone);
        }
    }

    public function normalizeMajor(string $major): string
    {
        $normalized = strtolower(trim($major));

        if ($normalized === '') {
            throw new InvalidArgumentException('Missing required query parameter: major');
        }

        $aliases = [
            'ko' => 'korea',
            'kr' => 'korea',
            'en' => 'english',
            'eng' => 'english',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    public function resolveTimezone(?string $timezone): string
    {
        $resolved = trim((string)($timezone ?? ''));
        if ($resolved === '') {
            return 'UTC';
        }

        if (!in_array($resolved, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException('Invalid timezone. Use a valid IANA timezone like Asia/Yangon.');
        }

        return $resolved;
    }

    public function resolveLocalDate(string $timezone): string
    {
        return CarbonImmutable::now($timezone)->toDateString();
    }

    public function stableIndex(string $major, string $localDate, int $count): int
    {
        if ($count <= 0) {
            throw new InvalidArgumentException('Count must be greater than zero');
        }

        $hash = md5($major . ':' . $localDate);
        $seed = hexdec(substr($hash, 0, 8));

        return $seed % $count;
    }

    private function selectDailyWord(string $major, string $localDate, string $timezone): array
    {
        $ids = WordOfDay::query()
            ->byMajor($major)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            throw new RuntimeException("No words found for major: {$major}");
        }

        $index = $this->stableIndex($major, $localDate, count($ids));
        $selectedId = $ids[$index];
        $word = WordOfDay::query()->find($selectedId);

        if (!$word) {
            throw new RuntimeException('Daily word could not be resolved');
        }

        return [
            'id' => (int)$word->id,
            'major' => $word->major,
            'word' => $word->word,
            'translation' => $word->translation,
            'speech' => $word->speech,
            'example' => $word->example,
            'thumb' => $word->thumb,
            'audio' => $word->audio,
            'date' => $localDate,
            'timezone' => $timezone,
        ];
    }

    private function cacheKey(string $major, string $localDate, string $timezone): string
    {
        return "word_of_day:{$major}:{$localDate}:{$timezone}";
    }

    private function ttlUntilNextMidnight(string $timezone): int
    {
        $now = CarbonImmutable::now($timezone);
        $nextMidnight = $now->addDay()->startOfDay();
        $seconds = $now->diffInSeconds($nextMidnight, false);

        return max(1, $seconds);
    }
}
