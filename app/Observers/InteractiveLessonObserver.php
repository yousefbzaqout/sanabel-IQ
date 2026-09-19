<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\InteractiveLesson;
use App\Services\Lessons\LessonEngineCache;

final class InteractiveLessonObserver
{
    public function saved(InteractiveLesson $lesson): void
    {
        LessonEngineCache::forgetLesson($lesson);
    }

    public function deleted(InteractiveLesson $lesson): void
    {
        LessonEngineCache::forgetLesson($lesson);
    }
}
