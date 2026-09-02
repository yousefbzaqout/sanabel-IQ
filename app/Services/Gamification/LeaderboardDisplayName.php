<?php

declare(strict_types=1);

namespace App\Services\Gamification;

final class LeaderboardDisplayName
{
    public static function format(string $fullName): string
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];

        if ($parts === []) {
            return __('Player');
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $firstName = $parts[0];
        $lastName = $parts[array_key_last($parts)];
        $lastInitial = mb_strtoupper(mb_substr($lastName, 0, 1));

        return "{$firstName} {$lastInitial}.";
    }
}
