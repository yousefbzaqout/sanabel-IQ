<?php

declare(strict_types=1);

namespace App\Services\Gamification;

final class StudentGamification
{
    public const XP_PER_LEVEL = 100;

    public static function levelForXp(int $totalXp): int
    {
        return (int) floor($totalXp / self::XP_PER_LEVEL) + 1;
    }

    public static function xpTowardsNextLevel(int $totalXp): int
    {
        return $totalXp % self::XP_PER_LEVEL;
    }

    public static function progressPercent(int $totalXp): int
    {
        return (int) round((self::xpTowardsNextLevel($totalXp) / self::XP_PER_LEVEL) * 100);
    }

    public static function xpRequiredForNextLevel(int $totalXp): int
    {
        return self::XP_PER_LEVEL - self::xpTowardsNextLevel($totalXp);
    }
}
