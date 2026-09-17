<?php

namespace Tests\Unit;

use App\Services\WordOfDayCsvService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WordOfDayCsvServiceTest extends TestCase
{
    public function test_it_parses_text_fields_without_image_or_audio_columns(): void
    {
        $path = $this->temporaryCsv(
            "\xEF\xBB\xBFword,translation,speech,example\n" .
            'Apple,ပန်းသီး,noun,"I eat apples, often."' . "\n"
        );

        try {
            $result = (new WordOfDayCsvService())->parse($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(0, $result['skipped']);
        $this->assertSame([
            'word' => 'Apple',
            'translation' => 'ပန်းသီး',
            'speech' => 'noun',
            'example' => 'I eat apples, often.',
        ], $result['items'][0]);
    }

    public function test_it_skips_rows_missing_a_word_or_translation(): void
    {
        $path = $this->temporaryCsv("word,translation,speech,example\n,Missing,noun,Example\nOrange,,noun,Example\nPear,သစ်တော်သီး,,\n");

        try {
            $result = (new WordOfDayCsvService())->parse($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(2, $result['skipped']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('Pear', $result['items'][0]['word']);
    }

    public function test_it_rejects_media_columns(): void
    {
        $path = $this->temporaryCsv("word,translation,image\nApple,ပန်းသီး,apple.jpg\n");

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unexpected CSV columns: image.');
            (new WordOfDayCsvService())->parse($path);
        } finally {
            unlink($path);
        }
    }

    public function test_template_only_contains_text_columns(): void
    {
        $service = new WordOfDayCsvService();

        $this->assertSame(['word', 'translation', 'speech', 'example'], $service->headers());
        $this->assertCount(4, $service->templateRows()[1]);
    }

    private function temporaryCsv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'word-of-day-csv-');
        file_put_contents($path, $contents);

        return $path;
    }
}
