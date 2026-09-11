<?php

namespace Tests\Unit;

use App\Services\FlashcardCsvService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FlashcardCsvServiceTest extends TestCase
{
    public function test_it_parses_utf8_csv_and_pipe_separated_list_fields(): void
    {
        $path = $this->temporaryCsv(
            "\xEF\xBB\xBFword,burmese_translation,ipa,pronunciation_audio,image,parts_of_speech,example_sentences,synonyms,antonyms,relatived\n" .
            'Apple,ပန်းသီး,/ˈæp.əl/,https://example.com/apple.mp3,https://example.com/apple.jpg,noun,"I eat apples, often.|Apples are fruit.",pome,,fruit|food' . "\n"
        );

        try {
            $result = (new FlashcardCsvService())->parse($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(0, $result['skipped']);
        $this->assertSame('Apple', $result['items'][0]['word']);
        $this->assertSame('ပန်းသီး', $result['items'][0]['burmese_translation']);
        $this->assertSame(['noun'], $result['items'][0]['parts_of_speech']);
        $this->assertSame(['I eat apples, often.', 'Apples are fruit.'], $result['items'][0]['example_sentences']);
        $this->assertSame(['fruit', 'food'], $result['items'][0]['relatived']);
    }

    public function test_it_ignores_blank_rows_and_skips_rows_without_a_word(): void
    {
        $path = $this->temporaryCsv("word,burmese_translation\n,Missing word\n\nOrange,လိမ္မော်သီး\n");

        try {
            $result = (new FlashcardCsvService())->parse($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('Orange', $result['items'][0]['word']);
    }

    public function test_it_rejects_csv_with_unexpected_columns(): void
    {
        $path = $this->temporaryCsv("word,translation_typo\nApple,ပန်းသီး\n");

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unexpected CSV columns: translation_typo.');
            (new FlashcardCsvService())->parse($path);
        } finally {
            unlink($path);
        }
    }

    private function temporaryCsv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flashcard-csv-');
        file_put_contents($path, $contents);

        return $path;
    }
}
