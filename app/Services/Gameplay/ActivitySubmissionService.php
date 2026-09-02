<?php

declare(strict_types=1);

namespace App\Services\Gameplay;

use App\Events\ActivityCompletedBroadcastEvent;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Services\Gamification\BadgeEvaluatorService;
use App\Services\Gamification\LeaderboardService;
use App\Services\Gamification\StreakTrackerService;
use App\Services\Goals\ParentGoalEvaluatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivitySubmissionService
{
    public function __construct(
        private readonly BadgeEvaluatorService $badgeEvaluator,
        private readonly LeaderboardService $leaderboardService,
        private readonly ParentGoalEvaluatorService $goalEvaluator,
        private readonly StreakTrackerService $streakTracker,
    ) {}

    /**
     * @param  list<int>  $answers
     * @return array{
     *     attempt: ActivityAttempt,
     *     score: int,
     *     total_questions: int,
     *     percentage: int,
     *     xp_earned: int,
     *     feedback: list<array<string, mixed>>
     * }
     */
    public function submit(Activity $activity, Student $student, array $answers): array
    {
        $result = DB::transaction(function () use ($activity, $student, $answers): array {
            Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->first();

            /** @var list<array<string, mixed>> $questions */
            $questions = array_values($activity->payload['questions'] ?? []);
            $totalQuestions = count($questions);
            $score = 0;
            $feedback = [];
            $answersJson = [];

            foreach ($questions as $index => $question) {
                $selectedIndex = (int) $answers[$index];
                $correctIndex = (int) $question['correct_index'];
                $isCorrect = $selectedIndex === $correctIndex;

                if ($isCorrect) {
                    $score++;
                }

                $answersJson[] = [
                    'selected_index' => $selectedIndex,
                    'correct_index' => $correctIndex,
                    'is_correct' => $isCorrect,
                ];

                $feedback[] = [
                    'question' => $question['question'] ?? '',
                    'selected_index' => $selectedIndex,
                    'correct_index' => $correctIndex,
                    'is_correct' => $isCorrect,
                    'explanation' => $question['explanation'] ?? '',
                ];
            }

            $hasPriorAttempt = ActivityAttempt::query()
                ->where('activity_id', $activity->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->exists();

            $xpEarned = 0;

            if (! $hasPriorAttempt && $totalQuestions > 0) {
                $xpEarned = (int) round(($score / $totalQuestions) * $activity->xp_reward);
            }

            $attempt = ActivityAttempt::query()->create([
                'activity_id' => $activity->id,
                'student_id' => $student->id,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'xp_earned' => $xpEarned,
                'answers_json' => $answersJson,
                'completed_at' => now(),
            ]);

            if ($xpEarned > 0) {
                $student->refresh();
                $student->addXp($xpEarned);
            }

            $percentage = $totalQuestions > 0
                ? (int) round(($score / $totalQuestions) * 100)
                : 0;

            return [
                'attempt' => $attempt,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'xp_earned' => $xpEarned,
                'feedback' => $feedback,
            ];
        });

        $freshStudent = $student->fresh();

        if ($freshStudent !== null) {
            $this->runPostSubmitSideEffects($freshStudent, $activity, $result);
        }

        return $result;
    }

    /**
     * @param  array{
     *     attempt: ActivityAttempt,
     *     score: int,
     *     total_questions: int,
     *     percentage: int,
     *     xp_earned: int,
     *     feedback: list<array<string, mixed>>
     * }  $result
     */
    private function runPostSubmitSideEffects(Student $freshStudent, Activity $activity, array $result): void
    {
        try {
            $this->streakTracker->recordActivity($freshStudent);
        } catch (Throwable $exception) {
            Log::warning('Activity streak tracking failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->badgeEvaluator->evaluate($freshStudent);
        } catch (Throwable $exception) {
            Log::warning('Activity badge evaluation failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->leaderboardService->flushGradeLevel($freshStudent->grade_level);
        } catch (Throwable $exception) {
            Log::warning('Activity leaderboard cache flush failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->goalEvaluator->evaluate($freshStudent);
        } catch (Throwable $exception) {
            Log::warning('Activity parent goal evaluation failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            ActivityCompletedBroadcastEvent::dispatch(
                parentId: (int) $freshStudent->user_id,
                childName: $freshStudent->name,
                activityTitle: $activity->title,
                scorePercent: $result['percentage'],
                xpEarned: $result['xp_earned'],
            );
        } catch (Throwable $exception) {
            Log::warning('Activity completion broadcast failed after submission.', [
                'student_id' => $freshStudent->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
