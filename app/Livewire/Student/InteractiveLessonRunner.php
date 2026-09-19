<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\LearningMaterial;
use App\Support\Lessons\InteractiveLessonCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InteractiveLessonRunner extends Component
{
    public string $lessonKey = 'letter-raa';

    public int $currentState = 1;

    public string $quizUrl = '';

    public string $mascotState = 'happy';

    public const MIN_STATE = 1;

    public const MAX_STATE = 5;

    public function mount(string $lessonKey = 'letter-raa'): void
    {
        $this->lessonKey = $lessonKey;
        $lesson = InteractiveLessonCatalog::get($this->lessonKey);

        $materialId = (int) ($lesson['quiz_learning_material_id'] ?? 0);
        $material = $materialId > 0
            ? LearningMaterial::query()->published()->find($materialId)
            : null;

        if ($material === null) {
            $materialTitle = (string) ($lesson['quiz_material_title'] ?? '');
            $material = $materialTitle !== ''
                ? LearningMaterial::query()->published()->where('title', $materialTitle)->first()
                : null;
        }

        $this->quizUrl = $material !== null
            ? route('student.materials.quiz', $material)
            : route('student.dashboard');
    }

    public function nextState(): void
    {
        if ($this->currentState < self::MAX_STATE) {
            $this->currentState++;
            $this->syncMascotForState();
        }
    }

    public function previousState(): void
    {
        if ($this->currentState > self::MIN_STATE) {
            $this->currentState--;
            $this->syncMascotForState();
        }
    }

    public function goToState(int $state): void
    {
        if ($state < self::MIN_STATE || $state > self::MAX_STATE) {
            return;
        }

        $this->currentState = $state;
        $this->syncMascotForState();
    }

    public function render(): View
    {
        $lesson = InteractiveLessonCatalog::get($this->lessonKey);

        return view('livewire.student.interactive-lesson-runner', [
            'lesson' => $lesson,
        ]);
    }

    private function syncMascotForState(): void
    {
        $this->mascotState = match ($this->currentState) {
            3 => 'thinking',
            5 => 'happy',
            4 => 'encouraging',
            default => 'happy',
        };
    }
}
