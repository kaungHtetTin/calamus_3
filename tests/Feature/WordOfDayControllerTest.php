<?php

namespace Tests\Feature;

use App\Services\WordOfDayService;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WordOfDayControllerTest extends TestCase
{
    public function test_word_of_day_requires_major()
    {
        $mock = Mockery::mock(WordOfDayService::class);
        $mock->shouldReceive('getDailyWord')
            ->once()
            ->andThrow(new InvalidArgumentException('Missing required query parameter: major'));
        $this->app->instance(WordOfDayService::class, $mock);

        $response = $this->getJson('/api/word-of-day');

        $response->assertStatus(400)->assertJson([
            'success' => false,
            'error' => 'Missing required query parameter: major',
        ]);
    }

    public function test_word_of_day_rejects_invalid_timezone()
    {
        $mock = Mockery::mock(WordOfDayService::class);
        $mock->shouldReceive('getDailyWord')
            ->once()
            ->andThrow(new InvalidArgumentException('Invalid timezone. Use a valid IANA timezone like Asia/Yangon.'));
        $this->app->instance(WordOfDayService::class, $mock);

        $response = $this->getJson('/api/word-of-day?major=english&tz=Invalid/Timezone');

        $response->assertStatus(400)->assertJson([
            'success' => false,
            'error' => 'Invalid timezone. Use a valid IANA timezone like Asia/Yangon.',
        ]);
    }

    public function test_word_of_day_returns_404_when_major_has_no_words()
    {
        $mock = Mockery::mock(WordOfDayService::class);
        $mock->shouldReceive('getDailyWord')
            ->once()
            ->andThrow(new RuntimeException('No words found for major: english'));
        $this->app->instance(WordOfDayService::class, $mock);

        $response = $this->getJson('/api/word-of-day?major=english');

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'error' => 'No words found for major: english',
        ]);
    }

    public function test_word_of_day_returns_single_word_payload()
    {
        $mock = Mockery::mock(WordOfDayService::class);
        $mock->shouldReceive('getDailyWord')
            ->once()
            ->andReturn([
                'id' => 15,
                'major' => 'english',
                'word' => 'resilient',
                'translation' => 'strong',
                'speech' => 'adjective',
                'example' => 'She stayed resilient through challenges.',
                'thumb' => null,
                'audio' => null,
                'date' => '2026-03-12',
                'timezone' => 'UTC',
            ]);
        $this->app->instance(WordOfDayService::class, $mock);

        $response = $this->getJson('/api/word-of-day?major=english&tz=UTC');

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'id' => 15,
                'major' => 'english',
                'word' => 'resilient',
                'translation' => 'strong',
                'speech' => 'adjective',
                'date' => '2026-03-12',
                'timezone' => 'UTC',
            ],
        ]);
    }
}
