<?php

declare(strict_types=1);

namespace App\Services\Gameplay;

use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ActivitySubmissionService
{
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
        return DB::transaction(function () use ($activity, $student, $answers): array {
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
    }
}
