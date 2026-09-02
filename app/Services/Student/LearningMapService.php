<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\Subject;
use Illuminate\Support\Collection;

class LearningMapService
{
    /**
     * @return list<array{
     *     id: int,
     *     title: string,
     *     subject_id: int,
     *     material_id: int|null,
     *     state: 'completed'|'available'|'locked',
     *     url: string|null,
     *     icon: string|null,
     *     position: int
     * }>
     */
    public function buildNodes(Student $student): array
    {
        /** @var Collection<int, Subject> $subjects */
        $subjects = Subject::query()
            ->where('grade_level', $student->grade_level)
            ->whereHas('learningMaterials', fn ($query) => $query->published())
            ->with(['learningMaterials' => fn ($query) => $query->published()->orderBy('order_column')->orderBy('id')])
            ->orderBy('id')
            ->get();

        $completedSubjectIds = $this->completedSubjectIds($student, $subjects);

        $nodes = [];
        $foundAvailable = false;

        foreach ($subjects->values() as $index => $subject) {
            /** @var LearningMaterial|null $material */
            $material = $subject->learningMaterials->first();
            $isCompleted = $completedSubjectIds->contains($subject->id);

            if ($isCompleted) {
                $state = 'completed';
            } elseif (! $foundAvailable) {
                $state = 'available';
                $foundAvailable = true;
            } else {
                $state = 'locked';
            }

            $nodes[] = [
                'id' => $subject->id,
                'title' => $subject->name,
                'subject_id' => $subject->id,
                'material_id' => $material?->id,
                'state' => $state,
                'url' => $material !== null && $state !== 'locked'
                    ? route('student.materials.quiz', $material)
                    : null,
                'icon' => $subject->icon,
                'position' => $index + 1,
            ];
        }

        return $nodes;
    }

    /**
     * URL of the next unfinished (available) subject quiz in the student's grade.
     */
    public function nextAvailableChallengeUrl(Student $student, ?int $excludeMaterialId = null): ?string
    {
        foreach ($this->buildNodes($student) as $node) {
            if ($node['state'] !== 'available' || $node['url'] === null) {
                continue;
            }

            if ($excludeMaterialId !== null && $node['material_id'] === $excludeMaterialId) {
                continue;
            }

            return $node['url'];
        }

        return null;
    }

    /**
     * @param  Collection<int, Subject>  $subjects
     * @return Collection<int, int>
     */
    private function completedSubjectIds(Student $student, Collection $subjects): Collection
    {
        if ($subjects->isEmpty()) {
            return collect();
        }

        $materialIds = $subjects
            ->flatMap(fn (Subject $subject) => $subject->learningMaterials->pluck('id'))
            ->unique()
            ->values();

        if ($materialIds->isEmpty()) {
            return collect();
        }

        $completedMaterialIds = StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereIn('learning_material_id', $materialIds)
            ->where('xp_earned', '>', 0)
            ->pluck('learning_material_id')
            ->unique();

        return $subjects
            ->filter(function (Subject $subject) use ($completedMaterialIds): bool {
                $subjectMaterialIds = $subject->learningMaterials->pluck('id');

                return $subjectMaterialIds->isNotEmpty()
                    && $subjectMaterialIds->diff($completedMaterialIds)->isEmpty();
            })
            ->pluck('id')
            ->values();
    }
}
