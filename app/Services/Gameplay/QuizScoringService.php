<?php

declare(strict_types=1);

namespace App\Services\Gameplay;

use App\Events\ActivityCompletedBroadcastEvent;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\StudentQuizAnswer;
use App\Services\Gamification\BadgeEvaluatorService;
use App\Services\Gamification\LeaderboardService;
use App\Services\Gamification\StreakTrackerService;
use App\Services\Goals\ParentGoalEvaluatorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuizScoringService
{
    public function __construct(
        private readonly BadgeEvaluatorService $badgeEvaluator,
        private readonly LeaderboardService $leaderboardService,
        private readonly ParentGoalEvaluatorService $goalEvaluator,
        private readonly StreakTrackerService $streakTracker,
    ) {}

    /**
     * @param  list<array{question_id: int, selected_option_id: int}>  $answers
     * @return array{
     *     attempt: StudentQuizAttempt,
     *     score: int,
     *     total_questions: int,
     *     percentage: int,
     *     score_percentage: float,
     *     xp_earned: int,
     *     feedback: list<array<string, mixed>>
     * }
     */
    public function submit(LearningMaterial $material, Student $student, array $answers): array
    {
        $result = DB::transaction(function () use ($material, $student, $answers): array {
            Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->first();

            /** @var Collection<int, Question> $questions */
            $questions = $material->questions()->with('options')->orderBy('order_column')->get();
            $answersByQuestionId = collect($answers)->keyBy('question_id');
            $totalQuestions = $questions->count();
            $correctAnswers = 0;
            $feedback = [];
            $answerRows = [];

            foreach ($questions as $question) {
                /** @var array{question_id: int, selected_option_id: int}|null $submitted */
                $submitted = $answersByQuestionId->get($question->id);

                $selectedOptionId = (int) ($submitted['selected_option_id'] ?? 0);
                /** @var QuestionOption|null $selectedOption */
                $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
                $isCorrect = $selectedOption !== null && $selectedOption->is_correct;
                $pointsAwarded = $isCorrect ? (int) $question->points : 0;

                if ($isCorrect) {
                    $correctAnswers++;
                }

                $correctOption = $question->options->firstWhere('is_correct', true);

                $answerRows[] = [
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOptionId,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                ];

                $feedback[] = [
                    'question_id' => $question->id,
                    'prompt' => $question->prompt,
                    'selected_option_id' => $selectedOptionId,
                    'correct_option_id' => $correctOption?->id,
                    'is_correct' => $isCorrect,
                    'explanation' => $question->explanation ?? '',
                ];
            }

            $hasPriorAttempt = StudentQuizAttempt::query()
                ->where('student_id', $student->id)
                ->where('learning_material_id', $material->id)
                ->lockForUpdate()
                ->exists();

            $xpEarned = 0;

            if (! $hasPriorAttempt && $totalQuestions > 0) {
                $xpEarned = (int) round(($correctAnswers / $totalQuestions) * $material->xp_reward);
            }

            $scorePercentage = $totalQuestions > 0
                ? round(($correctAnswers / $totalQuestions) * 100, 2)
                : 0.0;

            $attempt = StudentQuizAttempt::query()->create([
                'student_id' => $student->id,
                'learning_material_id' => $material->id,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score_percentage' => $scorePercentage,
                'xp_earned' => $xpEarned,
                'completed_at' => now(),
            ]);

            foreach ($answerRows as $row) {
                StudentQuizAnswer::query()->create([
                    'attempt_id' => $attempt->id,
                    ...$row,
                ]);
            }

            if ($xpEarned > 0) {
                $student->refresh();
                $student->addXp($xpEarned);
            }

            $percentage = $totalQuestions > 0
                ? (int) round(($correctAnswers / $totalQuestions) * 100)
                : 0;

            return [
                'attempt' => $attempt,
                'score' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'score_percentage' => (float) $scorePercentage,
                'xp_earned' => $xpEarned,
                'feedback' => $feedback,
            ];
        });

        $freshStudent = $student->fresh();

        if ($freshStudent !== null) {
            $this->runPostSubmitSideEffects($freshStudent, $material, $result);
        }

        return $result;
    }

    /**
     * @param  array{
     *     attempt: StudentQuizAttempt,
     *     score: int,
     *     total_questions: int,
     *     percentage: int,
     *     score_percentage: float,
     *     xp_earned: int,
     *     feedback: list<array<string, mixed>>
     * }  $result
     */
    private function runPostSubmitSideEffects(Student $freshStudent, LearningMaterial $material, array $result): void
    {
        try {
            $this->streakTracker->recordActivity($freshStudent);
        } catch (Throwable $exception) {
            Log::warning('Quiz streak tracking failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->badgeEvaluator->evaluate($freshStudent);
        } catch (Throwable $exception) {
            Log::warning('Quiz badge evaluation failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->leaderboardService->flushGradeLevel($freshStudent->grade_level);
        } catch (Throwable $exception) {
            Log::warning('Quiz leaderboard cache flush failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->goalEvaluator->evaluate($freshStudent);
        } catch (Throwable $exception) {
            Log::warning('Quiz parent goal evaluation failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            ActivityCompletedBroadcastEvent::dispatch(
                parentId: (int) $freshStudent->user_id,
                childName: $freshStudent->name,
                activityTitle: $material->title,
                scorePercent: $result['percentage'],
                xpEarned: $result['xp_earned'],
            );
        } catch (Throwable $exception) {
            Log::warning('Quiz completion broadcast failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
