<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Models\Activity;
use App\Models\ParentMaterial;
use App\Services\AI\ActivityGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateActivityFromMaterialJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public ParentMaterial $parentMaterial,
        public int $studentId,
    ) {}

    public function handle(ActivityGeneratorService $activityGeneratorService): void
    {
        $material = $this->parentMaterial->fresh(['student']);

        if ($material === null) {
            return;
        }

        if ($material->status !== MaterialStatus::Completed) {
            return;
        }

        if ($material->student_id !== $this->studentId) {
            Log::error('Activity generation aborted due to student context mismatch.', [
                'parent_material_id' => $material->id,
                'expected_student_id' => $this->studentId,
                'material_student_id' => $material->student_id,
            ]);

            return;
        }

        try {
            $generated = $activityGeneratorService->generate($material);

            Activity::query()->create([
                'student_id' => $this->studentId,
                'parent_material_id' => $material->id,
                'title' => $generated['title'],
                'payload' => $generated['payload'],
                'xp_reward' => $generated['xp_reward'],
                'status' => ActivityStatus::Published,
            ]);
        } catch (Throwable $exception) {
            Log::error('Activity generation failed.', [
                'parent_material_id' => $material->id,
                'student_id' => $this->studentId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
