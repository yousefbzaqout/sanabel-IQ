<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MasteryConcept;
use App\Services\Lessons\LessonEngineCache;

final class MasteryConceptObserver
{
    public function saved(MasteryConcept $concept): void
    {
        LessonEngineCache::forgetConcepts();
    }

    public function deleted(MasteryConcept $concept): void
    {
        LessonEngineCache::forgetConcepts();
    }
}
