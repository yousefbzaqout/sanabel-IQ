<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\InteractiveLessonStation;
use App\Services\Lessons\LessonEngineCache;

final class InteractiveLessonStationObserver
{
    public function saved(InteractiveLessonStation $station): void
    {
        $station->loadMissing('interactiveLesson');
        LessonEngineCache::forgetLesson($station->interactiveLesson);
    }

    public function deleted(InteractiveLessonStation $station): void
    {
        $station->loadMissing('interactiveLesson');
        LessonEngineCache::forgetLesson($station->interactiveLesson);
    }
}
