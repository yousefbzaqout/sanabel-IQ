<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\ParentMaterial;
use App\Services\AI\Schemas\ActivitySchema;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class ActivityGeneratorService
{
    /**
     * @return array{
     *     title: string,
     *     payload: array{questions: list<array<string, mixed>>},
     *     xp_reward: int
     * }
     */
    public function generate(ParentMaterial $material): array
    {
        $material->loadMissing(['student', 'materialChunks']);

        $student = $material->student;

        if ($student === null) {
            throw new RuntimeException('Material is not linked to a student.');
        }

        $materialContext = $material->materialChunks()
            ->orderBy('chunk_index')
            ->pluck('content')
            ->filter(fn (string $content): bool => trim($content) !== '')
            ->implode("\n\n");

        if ($materialContext === '') {
            throw new RuntimeException('No material chunks available for activity generation.');
        }

        $provider = (string) config('services.generation.provider', 'openrouter');
        $model = (string) config('services.generation.model', 'google/gemini-2.0-flash-001');

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSystemPrompt($this->systemPrompt())
            ->withPrompt($this->buildPrompt(
                materialTitle: $material->title,
                materialType: $material->type->value,
                gradeLevel: $student->grade_level,
                schoolTerm: $student->school_term,
                materialContext: $materialContext,
            ))
            ->withSchema(ActivitySchema::make())
            ->asStructured();

        $structured = $response->structured;

        if (! is_array($structured) || ! isset($structured['title'], $structured['questions'], $structured['total_xp'])) {
            throw new RuntimeException('Activity generation returned an invalid structured response.');
        }

        return [
            'title' => (string) $structured['title'],
            'payload' => [
                'questions' => array_values($structured['questions']),
            ],
            'xp_reward' => (int) $structured['total_xp'],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert Arabic educational content designer for Iraqi primary school students (grades 1-6).
Generate engaging, age-appropriate interactive quiz questions strictly from the provided material context.
Use Modern Standard Arabic suitable for children. Mix multiple choice, true/false, and fill-in-the-blank style questions.
Every question must include plausible distractors and a helpful explanation.
PROMPT;
    }

    private function buildPrompt(
        string $materialTitle,
        string $materialType,
        int $gradeLevel,
        int $schoolTerm,
        string $materialContext,
    ): string {
        return <<<PROMPT
Material title: {$materialTitle}
Material type: {$materialType}
Student grade level: {$gradeLevel}
School term: {$schoolTerm}

Extracted material content:
{$materialContext}

Create exactly 5 questions aligned with the student's grade level and the material above.
PROMPT;
    }
}
