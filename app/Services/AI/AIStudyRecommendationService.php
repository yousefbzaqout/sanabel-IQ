<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Models\StudyRecommendation;
use App\Services\AI\Schemas\StudyRecommendationSchema;
use App\Services\Analytics\SubjectAnalyticsService;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class AIStudyRecommendationService
{
    public function __construct(private readonly SubjectAnalyticsService $subjectAnalytics) {}

    public function generate(Student $student): StudyRecommendation
    {
        $analysis = $this->subjectAnalytics->analyze($student);
        $weakTopics = $this->subjectAnalytics->identifyWeaknesses($student);

        $provider = (string) config('services.generation.provider', 'openrouter');
        $model = (string) config('services.generation.model', 'google/gemini-2.0-flash-001');

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSystemPrompt($this->systemPrompt())
            ->withPrompt($this->buildPrompt($student, $analysis, $weakTopics->all()))
            ->withSchema(StudyRecommendationSchema::make())
            ->asStructured();

        $structured = $response->structured;

        if (! is_array($structured)) {
            throw new RuntimeException('Study recommendation generation returned an invalid structured response.');
        }

        $focusArea = trim((string) ($structured['focus_area'] ?? ''));
        $parentTips = $structured['parent_tips'] ?? null;

        if ($focusArea === '' || ! is_array($parentTips) || $parentTips === []) {
            throw new RuntimeException('Study recommendation generation returned incomplete structured data.');
        }

        /** @var list<string> $normalizedTips */
        $normalizedTips = array_values(array_filter(
            array_map(static fn (mixed $tip): string => trim((string) $tip), $parentTips),
            static fn (string $tip): bool => $tip !== '',
        ));

        if ($normalizedTips === []) {
            throw new RuntimeException('Study recommendation generation returned no actionable tips.');
        }

        return StudyRecommendation::query()->create([
            'student_id' => $student->id,
            'weak_topics_json' => $weakTopics->map(static fn (array $topic): array => [
                'subject' => $topic['subject'],
                'accuracy_percent' => $topic['accuracy_percent'],
            ])->values()->all(),
            'actionable_tips_json' => $normalizedTips,
            'suggested_focus_area' => $focusArea,
            'generated_at' => now(),
        ]);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert Arabic educational coach for Iraqi primary school parents (grades 1-6).
Provide concise, practical home-study guidance based on the child's performance analytics.
Respond only through the structured schema. Use Modern Standard Arabic suitable for parents.
PROMPT;
    }

    /**
     * @param array{
     *     total_questions_attempted: int,
     *     overall_accuracy_percent: int,
     *     subject_breakdown: list<array<string, mixed>>
     * } $analysis
     * @param  list<array<string, mixed>>  $weakTopics
     */
    private function buildPrompt(Student $student, array $analysis, array $weakTopics): string
    {
        $recentAttempts = ActivityAttempt::query()
            ->with(['activity.parentMaterial'])
            ->where('student_id', $student->id)
            ->latest('completed_at')
            ->limit(5)
            ->get()
            ->map(function (ActivityAttempt $attempt): string {
                $subject = $attempt->activity?->parentMaterial?->title ?? $attempt->activity?->title ?? 'Activity';
                $accuracy = $attempt->total_questions > 0
                    ? (int) round(($attempt->score / $attempt->total_questions) * 100)
                    : 0;

                return "- {$subject}: {$attempt->score}/{$attempt->total_questions} ({$accuracy}%)";
            })
            ->implode("\n");

        $weakTopicsSummary = collect($weakTopics)
            ->map(fn (array $topic): string => "- {$topic['subject']}: {$topic['accuracy_percent']}%")
            ->implode("\n");

        if ($weakTopicsSummary === '') {
            $weakTopicsSummary = '- No subjects below 60% accuracy were detected.';
        }

        $subjectBreakdown = collect($analysis['subject_breakdown'])
            ->map(fn (array $subject): string => "- {$subject['subject']}: {$subject['accuracy_percent']}%")
            ->implode("\n");

        return <<<PROMPT
Student grade level: {$student->grade_level}
School term: {$student->school_term}
Overall accuracy: {$analysis['overall_accuracy_percent']}%
Total questions attempted: {$analysis['total_questions_attempted']}

Subject performance breakdown:
{$subjectBreakdown}

Weak topics requiring focus (accuracy below 60%):
{$weakTopicsSummary}

Recent activity attempts:
{$recentAttempts}

Generate a focused study plan for the parent with practical home tips.
PROMPT;
    }
}
