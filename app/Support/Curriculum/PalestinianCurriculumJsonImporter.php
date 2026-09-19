<?php

declare(strict_types=1);

namespace App\Support\Curriculum;

use App\Enums\QuestionType;
use App\Models\LearningMaterial;
use App\Models\MasteryConcept;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Support\Lessons\CurriculumInteractiveLessonImporter;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class PalestinianCurriculumJsonImporter
{
    public function __construct(
        private readonly PalestinianCurriculumTransformer $transformer,
        private readonly CurriculumInteractiveLessonImporter $lessonImporter,
    ) {}

    /**
     * Import all Grade 1 Palestinian curriculum subjects found under
     * database/data/palestinian_curriculum/grade1/{subject}/outline.json
     *
     * @return list<string> Imported lesson keys
     */
    public function importPrototype(): array
    {
        return $this->importGrade(1);
    }

    /**
     * @return list<string> Imported lesson keys
     */
    public function importGrade(int $grade, ?string $curriculumRoot = null): array
    {
        if ($grade < 1 || $grade > 6) {
            throw new RuntimeException('Grade must be between 1 and 6.');
        }

        $root = $curriculumRoot ?? database_path('data/palestinian_curriculum');
        $gradeRoot = rtrim($root, '/').'/grade'.$grade;
        if (! is_dir($gradeRoot)) {
            throw new RuntimeException("Missing grade{$grade} curriculum root: {$gradeRoot}");
        }

        $lessonKeys = [];
        $subjectDirs = File::directories($gradeRoot);
        sort($subjectDirs);

        foreach ($subjectDirs as $subjectDir) {
            $outlinePath = $subjectDir.'/outline.json';
            if (! is_file($outlinePath)) {
                continue;
            }

            $lessonKeys = array_merge($lessonKeys, $this->importOutline($outlinePath, $subjectDir));
        }

        if ($lessonKeys === []) {
            throw new RuntimeException("No Palestinian curriculum outlines found under grade{$grade}/");
        }

        return $lessonKeys;
    }

    /**
     * @return list<string>
     */
    public function importOutline(string $outlinePath, ?string $baseDir = null): array
    {
        if (! is_file($outlinePath)) {
            throw new RuntimeException("Missing outline: {$outlinePath}");
        }

        $baseDir ??= dirname($outlinePath);

        /** @var array<string, mixed> $outline */
        $outline = json_decode((string) File::get($outlinePath), true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string, mixed> $subjectData */
        $subjectData = $outline['subject'];
        $subject = Subject::query()->updateOrCreate(
            [
                'code' => (string) $subjectData['code'],
                'grade_level' => (int) $outline['grade_level'],
            ],
            [
                'name' => (string) $subjectData['name'],
                'slug' => (string) $subjectData['slug'],
                'icon' => (string) ($subjectData['icon'] ?? ''),
                'description' => (string) ($subjectData['description'] ?? ''),
            ],
        );

        $lessonKeys = [];

        /** @var list<array<string, mixed>> $units */
        $units = $outline['units'] ?? [];
        foreach ($units as $unit) {
            /** @var list<string> $files */
            $files = $unit['lesson_files'] ?? [];
            foreach ($files as $relative) {
                $path = $baseDir.'/'.$relative;
                if (! is_file($path)) {
                    throw new RuntimeException("Missing lesson pack: {$path}");
                }

                /** @var array<string, mixed> $pack */
                $pack = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
                $definition = $this->transformer->toInteractiveDefinition($pack);

                $material = LearningMaterial::query()->updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'title' => (string) $pack['material_title'],
                    ],
                    [
                        'description' => (string) ($pack['description'] ?? ''),
                        'xp_reward' => (int) ($pack['xp_reward'] ?? 45),
                        'order_column' => (int) ($pack['order_column'] ?? 0),
                        'is_published' => true,
                    ],
                );

                $this->syncQuiz($material, $definition['quiz'] ?? ['questions' => []]);
                $this->syncMasteryHints($pack);
                $this->lessonImporter->import($definition, self::class);
                $lessonKeys[] = (string) $pack['lesson_key'];
            }
        }

        return $lessonKeys;
    }

    /**
     * @param  array{questions?: list<array<string, mixed>>}  $quiz
     */
    private function syncQuiz(LearningMaterial $material, array $quiz): void
    {
        $order = 0;
        foreach ($quiz['questions'] ?? [] as $questionData) {
            $order++;
            $type = QuestionType::tryFrom((string) ($questionData['type'] ?? 'mcq')) ?? QuestionType::Mcq;

            $question = Question::query()->updateOrCreate(
                [
                    'learning_material_id' => $material->id,
                    'prompt' => (string) $questionData['prompt'],
                ],
                [
                    'type' => $type,
                    'explanation' => (string) ($questionData['explanation'] ?? ''),
                    'points' => 1,
                    'order_column' => $order,
                ],
            );

            $optionOrder = 0;
            foreach ($questionData['options'] ?? [] as $option) {
                $optionOrder++;
                QuestionOption::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'order_column' => $optionOrder,
                    ],
                    [
                        'option_text' => (string) $option['text'],
                        'is_correct' => (bool) ($option['correct'] ?? false),
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pack
     */
    private function syncMasteryHints(array $pack): void
    {
        $adaptive = $pack['mascot_hints']['adaptive'] ?? [];
        if (! is_array($adaptive)) {
            return;
        }

        foreach ($adaptive as $row) {
            if (! is_array($row) || ! isset($row['concept'], $row['hint'])) {
                continue;
            }

            $code = (string) $row['concept'];
            $existing = MasteryConcept::query()->where('code', $code)->first();
            $meta = is_array($existing?->meta) ? $existing->meta : [];
            $overrides = is_array($meta['lesson_overrides'] ?? null) ? $meta['lesson_overrides'] : [];
            $overrides[(string) $pack['lesson_key']] = [
                'hint' => (string) $row['hint'],
            ];
            $meta['lesson_overrides'] = $overrides;

            MasteryConcept::query()->updateOrCreate(
                ['code' => $code],
                [
                    'label' => $existing?->label ?? $code,
                    'hint_template' => $existing?->hint_template ?? (string) $row['hint'],
                    'threshold' => $existing?->threshold ?? 2,
                    'meta' => $meta,
                ],
            );
        }
    }
}
