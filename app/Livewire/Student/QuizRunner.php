<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Services\Gameplay\QuizScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QuizRunner extends Component
{
    public int $learningMaterialId;

    public int $studentId;

    public int $currentIndex = 0;

    public string $mascotState = 'thinking';

    public ?bool $lastAnswerCorrect = null;

    public int $correctCount = 0;

    public int $progressPercent = 0;

    public bool $feedbackLocked = false;

    public bool $completed = false;

    public ?int $selectedOptionId = null;

    public ?int $revealedCorrectOptionId = null;

    /** @var array<int, int> */
    public array $answers = [];

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function mount(int $learningMaterialId, int $studentId): void
    {
        $this->learningMaterialId = $learningMaterialId;
        $this->studentId = $studentId;
        $this->mascotState = 'thinking';
    }

    #[Computed]
    public function material(): LearningMaterial
    {
        return LearningMaterial::query()
            ->published()
            ->with(['questions.options'])
            ->findOrFail($this->learningMaterialId);
    }

    #[Computed]
    public function student(): Student
    {
        return Student::query()->with('streak')->findOrFail($this->studentId);
    }

    /**
     * @return Collection<int, Question>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->material->questions->sortBy('order_column')->values();
    }

    #[Computed]
    public function currentQuestion(): ?Question
    {
        return $this->questions->get($this->currentIndex);
    }

    #[Computed]
    public function totalQuestions(): int
    {
        return $this->questions->count();
    }

    #[Computed]
    public function mascotMessage(): string
    {
        return match ($this->mascotState) {
            'happy' => 'أحسنت! إجابة صحيحة!',
            'encouraging' => 'لا بأس، هيا نكمل معاً!',
            default => 'خذ وقتك وفكّر جيداً يا بطل!',
        };
    }

    public function selectAnswer(int $optionId): void
    {
        if ($this->completed || $this->feedbackLocked) {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null) {
            return;
        }

        /** @var QuestionOption|null $option */
        $option = $question->options->firstWhere('id', $optionId);

        if ($option === null || $option->question_id !== $question->id) {
            return;
        }

        $correctOption = $question->options->firstWhere('is_correct', true);
        $isCorrect = (bool) $option->is_correct;

        $this->selectedOptionId = $optionId;
        $this->revealedCorrectOptionId = $correctOption?->id;
        $this->answers[$question->id] = $optionId;
        $this->lastAnswerCorrect = $isCorrect;
        $this->feedbackLocked = true;
        $this->mascotState = $isCorrect ? 'happy' : 'encouraging';

        if ($isCorrect) {
            $this->correctCount++;
            $this->progressPercent = $this->totalQuestions > 0
                ? (int) round(($this->correctCount / $this->totalQuestions) * 100)
                : 0;
            $this->dispatch('quiz-correct');
        } else {
            $this->dispatch('quiz-incorrect');
        }

        $this->dispatch('quiz-speak', message: $this->mascotMessage);
    }

    public function advanceAfterFeedback(): void
    {
        if ($this->completed || ! $this->feedbackLocked) {
            return;
        }

        if ($this->currentIndex >= $this->totalQuestions - 1) {
            $this->finalizeQuiz();

            return;
        }

        $this->currentIndex++;
        $this->feedbackLocked = false;
        $this->selectedOptionId = null;
        $this->revealedCorrectOptionId = null;
        $this->lastAnswerCorrect = null;
        $this->mascotState = 'thinking';
        $this->dispatch('quiz-speak', message: $this->currentQuestion?->prompt ?? $this->mascotMessage);
    }

    public function finalizeQuiz(): void
    {
        $material = $this->material;
        $student = $this->student;

        $payload = $this->questions->map(fn (Question $question): array => [
            'question_id' => $question->id,
            'selected_option_id' => $this->answers[$question->id] ?? 0,
        ])->values()->all();

        $result = app(QuizScoringService::class)->submit($material, $student, $payload);

        session([
            'quiz_celebration' => [
                'learning_material_id' => $material->id,
                'xp_earned' => (int) $result['xp_earned'],
                'percentage' => (int) $result['percentage'],
                'streak_days' => (int) ($result['streak_days'] ?? 0),
                'badge_ids' => array_values($result['unlocked_badge_ids'] ?? []),
            ],
        ]);

        $this->completed = true;
        $this->feedbackLocked = true;
        $this->result = [
            'score' => $result['score'],
            'total_questions' => $result['total_questions'],
            'percentage' => $result['percentage'],
            'xp_earned' => $result['xp_earned'],
        ];

        $this->redirect(route('student.quiz.completion', $material));
    }

    public function render(): View
    {
        return view('livewire.student.quiz-runner');
    }
}
