<?php

namespace App\Services;

use InvalidArgumentException;

class WordOfDayCsvService
{
    public function headers(): array
    {
        return ['word', 'translation', 'speech', 'example'];
    }

    public function templateRows(): array
    {
        return [
            $this->headers(),
            ['Apple', 'ပန်းသီး', 'noun', 'I eat an apple every day.'],
        ];
    }

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

            foreach (['word', 'translation'] as $requiredHeader) {
                if (!in_array($requiredHeader, $headers, true)) {
                    throw new InvalidArgumentException("The CSV header must include the required \"{$requiredHeader}\" column.");
                }
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

                if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    throw new InvalidArgumentException("CSV row {$rowNumber} has " . count($row) . ' columns; expected ' . count($headers) . '.');
                }

                $item = array_combine($headers, array_map(fn ($value) => trim((string) $value), $row));
                if (!is_array($item) || ($item['word'] ?? '') === '' || ($item['translation'] ?? '') === '') {
                    $skipped++;
                    continue;
                }

                if (mb_strlen($item['word']) > 255 || mb_strlen($item['translation']) > 255) {
                    throw new InvalidArgumentException("CSV row {$rowNumber} has a word or translation longer than 255 characters.");
                }
                if (isset($item['speech']) && mb_strlen($item['speech']) > 100) {
                    throw new InvalidArgumentException("CSV row {$rowNumber} has a speech value longer than 100 characters.");
                }

                $items[] = $item;
            }

            return ['items' => $items, 'skipped' => $skipped];
        } finally {
            fclose($handle);
        }
    }
}
