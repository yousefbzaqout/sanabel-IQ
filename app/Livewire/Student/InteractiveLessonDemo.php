<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\LearningMaterial;
use App\Models\Student;
use App\Services\AdaptiveMasteryEngine;
use App\Services\MascotAiFeedbackService;
use App\Support\Lessons\InteractiveLessonCatalog;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InteractiveLessonDemo extends Component
{
    public string $lessonKey = LetterRaaInteractiveLessonImporter::LESSON_KEY;

    public int $currentStation = 1;

    public string $quizUrl = '';

    public string $mascotState = 'happy';

    public string $mascotMessage = '';

    public string $mascotTone = 'intro';

    public int $attempts = 0;

    public int $timeSpent = 0;

    public int $masteryScore = 0;

    public bool $showMicroHint = false;

    public string $microHintMessage = '';

    public string $pendingHintConcept = '';

    public const MIN_STATION = 1;

    public const MAX_STATION = 6;

    public function mount(MascotAiFeedbackService $feedback, ?string $lessonKey = null): void
    {
        if ($lessonKey !== null && $lessonKey !== '') {
            $this->lessonKey = $lessonKey;
        }

        $lesson = InteractiveLessonCatalog::get($this->lessonKey);
        $this->quizUrl = $this->resolveQuizUrl($lesson);
        $this->applyFeedback($feedback->forStation($this->currentStation, $this->lessonKey), speak: false);
    }

    public function nextStation(MascotAiFeedbackService $feedback): void
    {
        if ($this->showMicroHint) {
            return;
        }

        if ($this->currentStation < self::MAX_STATION) {
            $this->currentStation++;
            $this->refreshStationFeedback($feedback);
        }
    }

    public function previousStation(MascotAiFeedbackService $feedback): void
    {
        if ($this->currentStation > self::MIN_STATION) {
            $this->currentStation--;
            $this->refreshStationFeedback($feedback);
        }
    }

    public function goToStation(int $station, MascotAiFeedbackService $feedback): void
    {
        if ($this->showMicroHint) {
            return;
        }

        if ($station < self::MIN_STATION || $station > self::MAX_STATION) {
            return;
        }

        $this->currentStation = $station;
        $this->refreshStationFeedback($feedback);
    }

    public function reportPerformance(
        int $attempts,
        int $timeSpent,
        int $masteryScore,
        MascotAiFeedbackService $feedback,
    ): void {
        $this->attempts = max(0, $attempts);
        $this->timeSpent = max(0, $timeSpent);
        $this->masteryScore = max(0, min(100, $masteryScore));

        $this->applyFeedback($feedback->generate([
            'attempts' => $this->attempts,
            'time_spent' => $this->timeSpent,
            'mastery_score' => $this->masteryScore,
            'station' => $this->currentStation,
            'lesson_key' => $this->lessonKey,
        ]));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function recordLessonError(
        string $conceptKey,
        array $context,
        AdaptiveMasteryEngine $engine,
        MascotAiFeedbackService $feedback,
    ): void {
        $student = $this->activeStudent();

        if ($student === null) {
            return;
        }

        $context['station'] = $context['station'] ?? $this->currentStation;
        $context['lesson_key'] = $this->lessonKey;

        $result = $engine->recordError($student, $conceptKey, $context);

        if ($result['show_micro_hint']) {
            $this->showMicroHint = true;
            $this->pendingHintConcept = $result['concept_key'];
            $this->microHintMessage = $result['hint_message'];
            $this->mascotState = 'encouraging';
            $this->mascotTone = 'encourage';
            $this->mascotMessage = $result['hint_message'];
            $this->dispatch('mascot-speak', message: $result['hint_message']);

            return;
        }

        $this->applyFeedback($feedback->generate([
            'attempts' => max($this->attempts, $result['error_count']),
            'time_spent' => max($this->timeSpent, 15),
            'mastery_score' => min($this->masteryScore ?: 40, 45),
            'station' => $this->currentStation,
            'lesson_key' => $this->lessonKey,
        ]));
    }

    public function dismissMicroHint(AdaptiveMasteryEngine $engine): void
    {
        $student = $this->activeStudent();

        if ($student !== null) {
            $engine->clearPendingHint($student, $this->lessonKey);
        }

        $this->showMicroHint = false;
        $this->pendingHintConcept = '';
        $this->microHintMessage = '';
    }

    public function render(): View
    {
        $lesson = InteractiveLessonCatalog::get($this->lessonKey);

        return view('livewire.student.interactive-lesson-demo', [
            'lesson' => $lesson,
        ]);
    }

    /**
     * @param  array<string, mixed>  $lesson
     */
    private function resolveQuizUrl(array $lesson): string
    {
        $materialId = (int) ($lesson['quiz_learning_material_id'] ?? 0);

        if ($materialId > 0) {
            $material = LearningMaterial::query()->published()->find($materialId);

            if ($material !== null) {
                return route('student.materials.quiz', $material);
            }
        }

        $materialTitle = (string) ($lesson['quiz_material_title'] ?? '');

        if ($materialTitle !== '') {
            $material = LearningMaterial::query()
                ->published()
                ->where('title', $materialTitle)
                ->first();

            if ($material !== null) {
                return route('student.materials.quiz', $material);
            }
        }

        return route('student.dashboard');
    }

    private function activeStudent(): ?Student
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $activeStudentId = (int) session('active_student_id');

        if ($activeStudentId <= 0) {
            return null;
        }

        return $user->students()->find($activeStudentId);
    }

    private function refreshStationFeedback(MascotAiFeedbackService $feedback): void
    {
        if ($this->attempts > 0 || $this->masteryScore > 0) {
            $this->applyFeedback($feedback->generate([
                'attempts' => $this->attempts,
                'time_spent' => $this->timeSpent,
                'mastery_score' => $this->masteryScore,
                'station' => $this->currentStation,
                'lesson_key' => $this->lessonKey,
            ]));

            return;
        }

        $this->applyFeedback($feedback->forStation($this->currentStation, $this->lessonKey));
    }

    /**
     * @param  array{tone: string, mascot_state: string, message: string, audio_prompt: string, station_hint?: string}  $feedback
     */
    private function applyFeedback(array $feedback, bool $speak = true): void
    {
        $this->mascotTone = $feedback['tone'];
        $this->mascotState = $feedback['mascot_state'];
        $this->mascotMessage = $feedback['message'];

        if ($speak) {
            $this->dispatch('mascot-speak', message: $feedback['audio_prompt']);
        }
    }
}
