<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Badge;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Services\Student\LearningMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QuizCelebration extends Component
{
    public int $learningMaterialId;

    public int $studentId;

    public int $xpEarned = 0;

    public int $percentage = 0;

    public int $streakDays = 0;

    /** @var list<int> */
    public array $badgeIds = [];

    public function mount(int $learningMaterialId, int $studentId): void
    {
        $this->learningMaterialId = $learningMaterialId;
        $this->studentId = $studentId;

        /** @var array<string, mixed>|null $payload */
        $payload = session('quiz_celebration');

        if (
            ! is_array($payload)
            || (int) ($payload['learning_material_id'] ?? 0) !== $learningMaterialId
        ) {
            $this->redirect(route('student.dashboard'));

            return;
        }

        $this->xpEarned = (int) ($payload['xp_earned'] ?? 0);
        $this->percentage = (int) ($payload['percentage'] ?? 0);
        $this->streakDays = (int) ($payload['streak_days'] ?? 0);
        $this->badgeIds = array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            $payload['badge_ids'] ?? [],
        ));
    }

    #[Computed]
    public function material(): LearningMaterial
    {
        return LearningMaterial::query()->findOrFail($this->learningMaterialId);
    }

    #[Computed]
    public function student(): Student
    {
        return Student::query()->findOrFail($this->studentId);
    }

    /**
     * @return Collection<int, Badge>
     */
    #[Computed]
    public function unlockedBadges(): Collection
    {
        if ($this->badgeIds === []) {
            return collect();
        }

        return Badge::query()
            ->whereIn('id', $this->badgeIds)
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function nextChallengeUrl(): string
    {
        $url = app(LearningMapService::class)->nextAvailableChallengeUrl(
            $this->student,
            $this->learningMaterialId,
        );

        return $url ?? route('student.dashboard');
    }

    public function render(): View
    {
        return view('livewire.student.quiz-celebration');
    }
}
