<?php

declare(strict_types=1);

namespace App\Support;

final class StudentNameNormalizer
{
    public static function normalize(string $name): string
    {
        $normalized = preg_replace('/[\x{061C}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $name) ?? $name;
        $normalized = preg_replace('/(\p{Arabic})(\p{Latin}+)/u', '$1', $normalized) ?? $normalized;
        $normalized = preg_replace('/(\p{Latin}+)(\p{Arabic})/u', '$1 $2', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? trim($normalized);

        return $normalized;
    }
}
