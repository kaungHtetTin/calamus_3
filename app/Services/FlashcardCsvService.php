<?php

namespace App\Services;

use InvalidArgumentException;

class FlashcardCsvService
{
    public function headers(): array
    {
        return [
            'word',
            'burmese_translation',
            'ipa',
            'pronunciation_audio',
            'image',
            'parts_of_speech',
            'example_sentences',
            'synonyms',
            'antonyms',
            'relatived',
        ];
    }

    public function templateRows(): array
    {
        return [
            $this->headers(),
            [
                'Apple',
                'ပန်းသီး',
                '/ˈæp.əl/',
                'https://example.com/audio/apple.mp3',
                'https://example.com/images/apple.jpg',
                'noun',
                'I eat an apple.|An apple a day keeps the doctor away.',
                'pome',
                '',
                'fruit|food',
            ],
        ];
    }

    /**
     * Parse a flashcard CSV into card-shaped arrays.
     *
     * Multi-value cells use a pipe (|) separator so normal punctuation and
     * commas in example sentences remain intact.
     */
    public function parse(string $path): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('The CSV file could not be read.');
        }

        try {
            $headerRow = fgetcsv($handle, 0, ',', '"', '');
            if (!is_array($headerRow)) {
                throw new InvalidArgumentException('The CSV file is empty.');
            }

            $headers = array_map(function ($header, $index) {
                $value = trim((string) $header);
                if ($index === 0) {
                    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
                }

                return strtolower((string) preg_replace('/[\s-]+/', '_', $value));
            }, $headerRow, array_keys($headerRow));

            if (!in_array('word', $headers, true)) {
                throw new InvalidArgumentException('The CSV header must include the required "word" column.');
            }

            $duplicates = array_keys(array_filter(array_count_values($headers), fn ($count) => $count > 1));
            if (!empty($duplicates)) {
                throw new InvalidArgumentException('The CSV contains duplicate columns: ' . implode(', ', $duplicates) . '.');
            }

            $unexpected = array_values(array_diff($headers, $this->headers()));
            if (!empty($unexpected)) {
                throw new InvalidArgumentException('Unexpected CSV columns: ' . implode(', ', $unexpected) . '. Please use the downloaded template.');
            }

            $items = [];
            $skipped = 0;
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $rowNumber++;

                if ($this->isBlankRow($row)) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    throw new InvalidArgumentException("CSV row {$rowNumber} has " . count($row) . ' columns; expected ' . count($headers) . '.');
                }

                $item = array_combine($headers, array_map(fn ($value) => trim((string) $value), $row));
                if (!is_array($item) || trim((string) ($item['word'] ?? '')) === '') {
                    $skipped++;
                    continue;
                }

                foreach (['parts_of_speech', 'example_sentences', 'synonyms', 'antonyms', 'relatived'] as $field) {
                    if (array_key_exists($field, $item)) {
                        $item[$field] = $this->splitList((string) $item[$field]);
                    }
                }

                $items[] = $item;
            }

            return ['items' => $items, 'skipped' => $skipped];
        } finally {
            fclose($handle);
        }
    }

    private function isBlankRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    private function splitList(string $value): array
    {
        return array_values(array_filter(
            array_map(fn ($item) => trim((string) $item), explode('|', $value)),
            fn ($item) => $item !== ''
        ));
    }
}
