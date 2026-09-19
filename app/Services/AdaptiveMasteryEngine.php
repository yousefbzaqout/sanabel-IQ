<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LessonAnalytic;
use App\Models\MasteryConcept;
use App\Models\Student;
use App\Services\Lessons\LessonEngineCache;
use Illuminate\Support\Facades\Session;

final class AdaptiveMasteryEngine
{
    public const ERROR_THRESHOLD = 2;

    public const CONCEPT_DIACRITIC = 'diacritic_confusion';

    public const CONCEPT_BUBBLE = 'bubble_sequence';

    public const CONCEPT_TRACE = 'incomplete_trace';

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     show_micro_hint: bool,
     *     error_count: int,
     *     concept_key: string,
     *     hint_message: string,
     *     analytic_id: int|null
     * }
     */
    public function recordError(Student $student, string $conceptKey, array $context = []): array
    {
        $conceptKey = $this->normalizeConcept($conceptKey);
        $station = isset($context['station']) ? (int) $context['station'] : null;
        $lessonKey = (string) ($context['lesson_key'] ?? 'letter-raa');
        $concept = $this->resolveConcept($conceptKey);
        $threshold = $concept?->threshold ?? self::ERROR_THRESHOLD;

        $sessionKey = $this->sessionCounterKey($student->id, $lessonKey, $conceptKey);
        $errorCount = (int) Session::get($sessionKey, 0) + 1;
        Session::put($sessionKey, $errorCount);

        $analytic = LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => $lessonKey,
            'station' => $station,
            'concept_key' => $conceptKey,
            'event_type' => 'error',
            'error_count' => $errorCount,
            'payload' => $context,
        ]);

        $showHint = $errorCount > $threshold;
        $hintMessage = $showHint ? $this->hintMessage($conceptKey, $lessonKey) : '';

        if ($showHint) {
            LessonAnalytic::query()->create([
                'student_id' => $student->id,
                'lesson_key' => $lessonKey,
                'station' => $station,
                'concept_key' => $conceptKey,
                'event_type' => 'micro_hint',
                'error_count' => $errorCount,
                'payload' => [
                    'hint' => $hintMessage,
                    'trigger' => $context,
                    'mastery_concept_id' => $concept?->id,
                ],
            ]);

            Session::put($this->sessionHintKey($student->id, $lessonKey), [
                'concept_key' => $conceptKey,
                'message' => $hintMessage,
                'station' => $station,
            ]);
        }

        return [
            'show_micro_hint' => $showHint,
            'error_count' => $errorCount,
            'concept_key' => $conceptKey,
            'hint_message' => $hintMessage,
            'analytic_id' => $analytic->id,
        ];
    }

    public function clearPendingHint(Student $student, string $lessonKey = 'letter-raa'): void
    {
        Session::forget($this->sessionHintKey($student->id, $lessonKey));
    }

    /**
     * @return array{concept_key: string, message: string, station: int|null}|null
     */
    public function pendingHint(Student $student, string $lessonKey = 'letter-raa'): ?array
    {
        /** @var array{concept_key?: string, message?: string, station?: int|null}|null $hint */
        $hint = Session::get($this->sessionHintKey($student->id, $lessonKey));

        if (! is_array($hint) || empty($hint['message'])) {
            return null;
        }

        return [
            'concept_key' => (string) ($hint['concept_key'] ?? ''),
            'message' => (string) $hint['message'],
            'station' => isset($hint['station']) ? (int) $hint['station'] : null,
        ];
    }

    public function errorCount(Student $student, string $conceptKey, string $lessonKey = 'letter-raa'): int
    {
        return (int) Session::get(
            $this->sessionCounterKey($student->id, $lessonKey, $this->normalizeConcept($conceptKey)),
            0,
        );
    }

    public function hintMessage(string $conceptKey, string $lessonKey = ''): string
    {
        $normalized = $this->normalizeConcept($conceptKey);
        $concept = $this->resolveConcept($normalized);

        if ($concept !== null) {
            /** @var array<string, mixed> $meta */
            $meta = $concept->meta ?? [];
            $overrides = is_array($meta['lesson_overrides'] ?? null) ? $meta['lesson_overrides'] : [];

            if ($lessonKey !== '' && isset($overrides[$lessonKey]['hint']) && is_string($overrides[$lessonKey]['hint'])) {
                return $overrides[$lessonKey]['hint'];
            }

            return $concept->hint_template;
        }

        return match ($normalized) {
            self::CONCEPT_DIACRITIC => 'تلميح سنبل: فرّق جيداً بين رَ (فتحة) و رُ (ضمة). افتح فمك قليلاً مع رَ!',
            self::CONCEPT_BUBBLE => 'تلميح سنبل: رتّب الفقاعات هكذا — رَ ثم مَ ثم لْ لتكوّن رَمَل.',
            self::CONCEPT_TRACE => 'تلميح سنبل: ابدأ من أعلى حرف الراء وانزل بالقوس بهدوء حتى النهاية.',
            default => 'تلميح سنبل: خذ نفساً عميقاً وحاول مرة أخرى يا بطل!',
        };
    }

    private function resolveConcept(string $conceptKey): ?MasteryConcept
    {
        $concept = LessonEngineCache::allConcepts()->get($conceptKey);

        return $concept instanceof MasteryConcept ? $concept : null;
    }

    private function normalizeConcept(string $conceptKey): string
    {
        $key = trim($conceptKey);

        return match ($key) {
            'diacritic', 'raa_damma', 'fatha_damma' => self::CONCEPT_DIACRITIC,
            'bubble', 'bubbles' => self::CONCEPT_BUBBLE,
            'trace', 'tracing' => self::CONCEPT_TRACE,
            default => $key,
        };
    }

    private function sessionCounterKey(int $studentId, string $lessonKey, string $conceptKey): string
    {
        return "lesson_analytics.{$studentId}.{$lessonKey}.{$conceptKey}.errors";
    }

    private function sessionHintKey(int $studentId, string $lessonKey): string
    {
        return "lesson_analytics.{$studentId}.{$lessonKey}.pending_hint";
    }
}
