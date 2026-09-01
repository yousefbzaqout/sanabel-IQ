<?php

declare(strict_types=1);

namespace App\Enums;

enum BadgeCriteriaType: string
{
    case QuizCount = 'quiz_count';
    case XpThreshold = 'xp_threshold';
    case StreakDays = 'streak_days';
    case ActivitiesCompleted = 'activities_completed';
    case PerfectScores = 'perfect_scores';
}
