<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Schemas\PalestinianLessonPackSchema;
use App\Support\Curriculum\PalestinianLessonSchema;
use Prism\Prism\Facades\Prism;
use RuntimeException;

final class PalestinianLessonGeneratorService
{
    /**
     * @param  array{index: int, title: string, content: string}  $chunk
     * @return array<string, mixed>
     */
    public function generateFromChunk(
        array $chunk,
        int $grade,
        string $subject,
        int $semester = 1,
    ): array {
        $subjectMeta = $this->subjectMeta($subject);
        $provider = (string) config('services.generation.provider', 'openrouter');
        $model = (string) config('services.generation.model', 'google/gemini-2.0-flash-001');
        $maxTokens = (int) config('services.generation.max_tokens', 8192);
        $timeout = (int) config('prism.request_timeout', 180);

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSystemPrompt($this->systemPrompt())
            ->withPrompt($this->buildPrompt($chunk, $grade, $semester, $subjectMeta))
            ->withSchema(PalestinianLessonPackSchema::make())
            ->withMaxTokens($maxTokens > 0 ? $maxTokens : 8192)
            ->withClientOptions(['timeout' => $timeout > 0 ? $timeout : 180])
            ->asStructured();

        $structured = $response->structured;
        if (! is_array($structured)) {
            throw new RuntimeException('Lesson generation returned an invalid structured response.');
        }

        $pack = $this->normalizePack($structured, $chunk, $grade, $semester, $subjectMeta);
        PalestinianLessonSchema::assertValid($pack);

        return $pack;
    }

    /**
     * @param  array<string, mixed>  $structured
     * @param  array{index: int, title: string, content: string}  $chunk
     * @param  array{code: string, content_kind: string, folder: string}  $subjectMeta
     * @return array<string, mixed>
     */
    private function normalizePack(
        array $structured,
        array $chunk,
        int $grade,
        int $semester,
        array $subjectMeta,
    ): array {
        $pack = $structured;
        $pack['schema_version'] = (string) ($pack['schema_version'] ?? '1.0.0');
        $pack['grade_level'] = $grade;
        $pack['semester'] = $semester;
        $pack['subject_code'] = $subjectMeta['code'];
        $pack['content_kind'] = $subjectMeta['content_kind'];

        $chunkIndex = max(0, (int) ($chunk['index'] ?? 0));
        $fallbackSlug = 'ai-chunk-'.$chunkIndex;
        $key = $this->nonEmptyString($pack['key'] ?? null)
            ?? $this->slugFromLessonKey($pack['lesson_key'] ?? null)
            ?? $fallbackSlug;
        $lessonKey = $this->nonEmptyString($pack['lesson_key'] ?? null)
            ?? sprintf('ar-g%d-%s-%s', $grade, $subjectMeta['folder'], $key);

        $pack['key'] = $key;
        $pack['lesson_key'] = $lessonKey;
        $pack['audio_slug'] = $this->nonEmptyString($pack['audio_slug'] ?? null) ?? $key;

        $title = $this->nonEmptyString($pack['title'] ?? null) ?? (string) ($chunk['title'] ?? 'درس');
        $pack['title'] = $title;
        $pack['subtitle'] = $this->nonEmptyString($pack['subtitle'] ?? null) ?? $title;
        $pack['material_title'] = $this->nonEmptyString($pack['material_title'] ?? null) ?? ('AI: '.$title);
        $pack['description'] = $this->nonEmptyString($pack['description'] ?? null) ?? $title;
        $pack['order_column'] = (int) ($pack['order_column'] ?? (100 + $chunkIndex));
        $pack['xp_reward'] = (int) ($pack['xp_reward'] ?? 45);

        if (isset($pack['quiz']['questions']) && is_array($pack['quiz']['questions'])) {
            $pack['quiz']['questions'] = array_map(static function (array $question): array {
                $question['options'] = array_map(static function (array $option): array {
                    $option['correct'] = (bool) ($option['correct'] ?? false);

                    return $option;
                }, $question['options'] ?? []);

                return $question;
            }, $pack['quiz']['questions']);
        }

        if ($subjectMeta['content_kind'] === 'number') {
            if (! isset($pack['digit']) && isset($pack['digits'][0])) {
                $pack['digit'] = (string) $pack['digits'][array_key_last($pack['digits'])];
            }
        } else {
            if (! isset($pack['letter']) || $pack['letter'] === '') {
                $glyph = $pack['phonemes']['voice_targets'][0] ?? 'أ';
                $pack['letter'] = mb_substr((string) $glyph, 0, 1);
            }
        }

        return $pack;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function slugFromLessonKey(mixed $lessonKey): ?string
    {
        $lessonKey = $this->nonEmptyString($lessonKey);
        if ($lessonKey === null) {
            return null;
        }

        if (preg_match('/(?:^|-)(ai-[a-z0-9-]+)$/i', $lessonKey, $matches) === 1) {
            return strtolower($matches[1]);
        }

        $parts = explode('-', $lessonKey);

        return strtolower((string) end($parts)) ?: null;
    }

    /**
     * @return array{code: string, content_kind: string, folder: string}
     */
    private function subjectMeta(string $subject): array
    {
        return match (strtolower($subject)) {
            'arabic', 'ar' => ['code' => 'AR', 'content_kind' => 'letter', 'folder' => 'arabic'],
            'math', 'mathematics', 'maths' => ['code' => 'MATH', 'content_kind' => 'number', 'folder' => 'math'],
            'science', 'sci' => ['code' => 'SCI', 'content_kind' => 'letter', 'folder' => 'science'],
            default => throw new RuntimeException("Unsupported subject: {$subject}"),
        };
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are Sanabel-IQ's curriculum engineer for Palestinian Grade 1–6 textbooks.
Produce ONE interactive lesson pack as structured JSON for children ages 5–12.
Use Modern Standard Arabic suitable for kids. Include Sanbal (سنبل) mascot hints.
For MATH use Eastern Arabic digits (١–٩), visual_items counting questions, and comparison questions (gt/lt/eq).
For ARABIC/SCIENCE use letter-focused phonemes when relevant.
Stroke.path must be a valid SVG path string inside a 140×140 viewBox.
Never invent political content. Stay faithful to the provided textbook chunk.
PROMPT;
    }

    /**
     * @param  array{index: int, title: string, content: string}  $chunk
     * @param  array{code: string, content_kind: string, folder: string}  $subjectMeta
     */
    private function buildPrompt(array $chunk, int $grade, int $semester, array $subjectMeta): string
    {
        $title = $chunk['title'];
        $content = mb_substr($chunk['content'], 0, 1200);
        $kind = $subjectMeta['content_kind'];
        $code = $subjectMeta['code'];

        return <<<PROMPT
Grade: {$grade}
Semester: {$semester}
Subject code: {$code}
Content kind: {$kind}
Chunk title: {$title}

Textbook chunk:
{$content}

Generate a complete Sanabel-IQ lesson pack for this chunk.
Set lesson_key like ar-g{$grade}-{$subjectMeta['folder']}-ai-<slug>.
Set key to a short slug used as the JSON filename (without .json).
Include at least one quiz question. For MATH include visual_items and/or comparison when possible.
PROMPT;
    }
}
