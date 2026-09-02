<?php

declare(strict_types=1);

namespace App\Services\Document;

class TextChunker
{
    /**
     * @return list<string>
     */
    public function chunk(string $text, int $maxLength = 1000, int $overlap = 100): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if ($maxLength <= $overlap) {
            throw new \InvalidArgumentException('Chunk max length must be greater than overlap.');
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunks[] = mb_substr($text, $start, $maxLength);
            $start += $maxLength - $overlap;

            if ($start >= $length) {
                break;
            }
        }

        return $chunks;
    }
}
