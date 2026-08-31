<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Student;
use App\Services\AI\AIStudyRecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateStudyRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $studentId) {}

    public function handle(AIStudyRecommendationService $recommendationService): void
    {
        $student = Student::query()->find($this->studentId);

        if ($student === null) {
            return;
        }

        try {
            $recommendationService->generate($student);
        } catch (Throwable $exception) {
            Log::error('Study recommendation generation failed.', [
                'student_id' => $this->studentId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
