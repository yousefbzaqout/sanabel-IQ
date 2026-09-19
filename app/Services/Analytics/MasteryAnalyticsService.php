<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\LessonAnalytic;
use App\Models\Student;
use Illuminate\Support\Collection;

final class MasteryAnalyticsService
{
    public const EVENT_LESSON_COMPLETE = 'lesson_complete';

    public const EVENT_STATION_COMPLETE = 'station_complete';

    public const EVENT_VOICE_ATTEMPT = 'voice_attempt';

    public const EVENT_TRACE_ATTEMPT = 'trace_attempt';

    public const EVENT_QUIZ_ATTEMPT = 'quiz_attempt';

    public const EVENT_DISCOVERY_ATTEMPT = 'discovery_attempt';

    public const EVENT_MICRO_HINT = 'micro_hint';

    /**
     * @return array{
     *     student_id: int|null,
     *     overview: array<string, int|float>,
     *     time_by_lesson: array<string, int>,
     *     time_by_station: array<string, int>,
     *     stations: array<string, array<string, int|float>>,
     *     ai_interventions: array{total_hints: int, by_concept: array<string, int>}
     * }
     */
    public function forStudent(Student $student): array
    {
        $events = LessonAnalytic::query()
            ->where('student_id', $student->id)
            ->get();

        $payload = $this->aggregate($events);
        $payload['student_id'] = $student->id;

        return $payload;
    }

    /**
     * Classroom / teacher overview across all students with telemetry.
     *
     * @return array{
     *     student_id: null,
     *     overview: array<string, int|float>,
     *     time_by_lesson: array<string, int>,
     *     time_by_station: array<string, int>,
     *     stations: array<string, array<string, int|float>>,
     *     ai_interventions: array{total_hints: int, by_concept: array<string, int>}
     * }
     */
    public function forClassroom(?int $studentId = null): array
    {
        $query = LessonAnalytic::query();

        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        $events = $query->get();
        $payload = $this->aggregate($events);
        $payload['student_id'] = $studentId;
        $payload['overview']['students_tracked'] = $events
            ->pluck('student_id')
            ->unique()
            ->count();

        return $payload;
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array{
     *     student_id: null,
     *     overview: array<string, int|float>,
     *     time_by_lesson: array<string, int>,
     *     time_by_station: array<string, int>,
     *     stations: array<string, array<string, int|float>>,
     *     ai_interventions: array{total_hints: int, by_concept: array<string, int>}
     * }
     */
    private function aggregate(Collection $events): array
    {
        $timeByLesson = $this->timeByLesson($events);
        $timeByStation = $this->timeByStation($events);
        $masteryScores = $this->masteryScoresByLesson($events);
        $touchedLessons = $events->pluck('lesson_key')->unique()->filter()->count();
        $completedLessons = $events
            ->where('event_type', self::EVENT_LESSON_COMPLETE)
            ->pluck('lesson_key')
            ->unique()
            ->count();

        $completionRate = $touchedLessons > 0
            ? (int) round(($completedLessons / $touchedLessons) * 100)
            : 0;

        $averageMastery = $masteryScores === []
            ? 0
            : (int) round(array_sum($masteryScores) / count($masteryScores));

        return [
            'student_id' => null,
            'overview' => [
                'completion_rate' => $completionRate,
                'average_mastery_score' => $averageMastery,
                'total_time_spent_seconds' => (int) array_sum($timeByLesson),
                'lessons_touched' => $touchedLessons,
                'lessons_completed' => $completedLessons,
            ],
            'time_by_lesson' => $timeByLesson,
            'time_by_station' => $timeByStation,
            'stations' => [
                'voice' => $this->voiceStats($events),
                'tracing' => $this->tracingStats($events),
                'quiz_discovery' => $this->quizDiscoveryStats($events),
            ],
            'ai_interventions' => $this->aiInterventionStats($events),
        ];
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array<string, int>
     */
    private function timeByLesson(Collection $events): array
    {
        $completedTimes = [];
        foreach ($events->where('event_type', self::EVENT_LESSON_COMPLETE) as $event) {
            $key = (string) $event->lesson_key;
            $completedTimes[$key] = (int) ($event->payload['time_spent'] ?? 0);
        }

        $stationSums = [];
        foreach ($events->where('event_type', self::EVENT_STATION_COMPLETE) as $event) {
            $key = (string) $event->lesson_key;
            $stationSums[$key] = ($stationSums[$key] ?? 0) + (int) ($event->payload['time_spent'] ?? 0);
        }

        $keys = array_unique([...array_keys($completedTimes), ...array_keys($stationSums)]);
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $completedTimes[$key] ?? ($stationSums[$key] ?? 0);
        }

        ksort($result);

        return $result;
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array<string, int>
     */
    private function timeByStation(Collection $events): array
    {
        $totals = [];
        foreach ($events->where('event_type', self::EVENT_STATION_COMPLETE) as $event) {
            if ($event->station === null) {
                continue;
            }
            $station = (string) $event->station;
            $totals[$station] = ($totals[$station] ?? 0) + (int) ($event->payload['time_spent'] ?? 0);
        }

        ksort($totals, SORT_NUMERIC);

        return $totals;
    }

    /**
     * Prefer lesson_complete mastery; otherwise latest/avg station mastery per lesson.
     *
     * @param  Collection<int, LessonAnalytic>  $events
     * @return list<int>
     */
    private function masteryScoresByLesson(Collection $events): array
    {
        $scores = [];

        foreach ($events->where('event_type', self::EVENT_LESSON_COMPLETE) as $event) {
            $scores[(string) $event->lesson_key] = (int) ($event->payload['mastery_score'] ?? 0);
        }

        $stationBuckets = [];
        foreach ($events->where('event_type', self::EVENT_STATION_COMPLETE) as $event) {
            $key = (string) $event->lesson_key;
            if (isset($scores[$key])) {
                continue;
            }
            $stationBuckets[$key][] = (int) ($event->payload['mastery_score'] ?? 0);
        }

        foreach ($stationBuckets as $key => $values) {
            $filtered = array_filter($values, static fn (int $v): bool => $v > 0);
            if ($filtered === []) {
                continue;
            }
            $scores[$key] = (int) round(array_sum($filtered) / count($filtered));
        }

        return array_values($scores);
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array{pronunciation_score: int, attempts: int}
     */
    private function voiceStats(Collection $events): array
    {
        $attempts = $events->where('event_type', self::EVENT_VOICE_ATTEMPT);
        $scores = $attempts
            ->map(static fn (LessonAnalytic $e): int => (int) ($e->payload['pronunciation_score'] ?? 0))
            ->all();

        return [
            'pronunciation_score' => $scores === [] ? 0 : (int) round(array_sum($scores) / count($scores)),
            'attempts' => $attempts->count(),
        ];
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array{stroke_accuracy: int, path_precision: int, attempts: int}
     */
    private function tracingStats(Collection $events): array
    {
        $attempts = $events->where('event_type', self::EVENT_TRACE_ATTEMPT);
        $accuracy = [];
        $precision = [];

        foreach ($attempts as $event) {
            $accuracy[] = (int) ($event->payload['stroke_accuracy'] ?? 0);
            $precision[] = (int) ($event->payload['path_precision'] ?? 0);
        }

        return [
            'stroke_accuracy' => $accuracy === [] ? 0 : (int) round(array_sum($accuracy) / count($accuracy)),
            'path_precision' => $precision === [] ? 0 : (int) round(array_sum($precision) / count($precision)),
            'attempts' => $attempts->count(),
        ];
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array{first_attempt_success_rate: int, attempts: int}
     */
    private function quizDiscoveryStats(Collection $events): array
    {
        $attempts = $events->filter(static function (LessonAnalytic $event): bool {
            return in_array($event->event_type, [self::EVENT_QUIZ_ATTEMPT, self::EVENT_DISCOVERY_ATTEMPT], true)
                && ($event->payload['first_attempt'] ?? false) === true;
        });

        $successes = $attempts->filter(
            static fn (LessonAnalytic $event): bool => ($event->payload['success'] ?? false) === true,
        )->count();

        $total = $attempts->count();

        return [
            'first_attempt_success_rate' => $total > 0 ? (int) round(($successes / $total) * 100) : 0,
            'attempts' => $total,
        ];
    }

    /**
     * @param  Collection<int, LessonAnalytic>  $events
     * @return array{total_hints: int, by_concept: array<string, int>}
     */
    private function aiInterventionStats(Collection $events): array
    {
        $hints = $events->where('event_type', self::EVENT_MICRO_HINT);
        $byConcept = [];

        foreach ($hints as $hint) {
            $concept = (string) $hint->concept_key;
            $byConcept[$concept] = ($byConcept[$concept] ?? 0) + 1;
        }

        ksort($byConcept);

        return [
            'total_hints' => $hints->count(),
            'by_concept' => $byConcept,
        ];
    }
}
