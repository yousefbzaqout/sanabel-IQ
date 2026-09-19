<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Idempotent Grade 1 / Semester 1 Palestinian curriculum ingestion.
 *
 * Maps textbook Subject → Unit (description metadata) → Lesson (LearningMaterial)
 * → Question / QuestionOption. Re-runs update existing rows without duplicates.
 */
class Grade1Semester1Seeder extends Seeder
{
    private const GRADE_LEVEL = 1;

    public function run(): void
    {
        $path = database_path('data/grade1_sem1/curriculum.php');

        if (! is_file($path)) {
            throw new RuntimeException("Missing Grade 1 curriculum fixture: {$path}");
        }

        /** @var list<array<string, mixed>> $subjects */
        $subjects = require $path;

        $globalLessonOrder = 0;

        foreach ($subjects as $subjectData) {
            $subject = Subject::query()->updateOrCreate(
                [
                    'code' => (string) $subjectData['code'],
                    'grade_level' => self::GRADE_LEVEL,
                ],
                [
                    'name' => (string) $subjectData['name'],
                    'slug' => (string) $subjectData['slug'],
                    'icon' => (string) ($subjectData['icon'] ?? ''),
                    'description' => (string) ($subjectData['description'] ?? ''),
                ],
            );

            /** @var list<array<string, mixed>> $units */
            $units = $subjectData['units'] ?? [];

            foreach ($units as $unit) {
                $outcomes = array_values(array_map(
                    static fn (mixed $outcome): string => (string) $outcome,
                    $unit['outcomes'] ?? [],
                ));
                $outcomesBlock = $outcomes === []
                    ? ''
                    : 'نواتج التعلم: '.implode('؛ ', $outcomes);

                /** @var list<array<string, mixed>> $lessons */
                $lessons = $unit['lessons'] ?? [];

                foreach ($lessons as $lesson) {
                    $globalLessonOrder++;

                    $descriptionParts = array_filter([
                        (string) ($lesson['description'] ?? ''),
                        'الوحدة: '.(string) $unit['title'],
                        $outcomesBlock,
                        'الصف الأول — الفصل الأول',
                    ]);

                    $material = LearningMaterial::query()->updateOrCreate(
                        [
                            'subject_id' => $subject->id,
                            'title' => (string) $lesson['title'],
                        ],
                        [
                            'description' => implode("\n", $descriptionParts),
                            'xp_reward' => (int) ($lesson['xp_reward'] ?? 50),
                            'order_column' => $globalLessonOrder,
                            'is_published' => true,
                        ],
                    );

                    $material->forceFill([
                        'audio_path' => 'audio/grade'.self::GRADE_LEVEL.'/material_'.$material->id.'_title.mp3',
                    ])->save();

                    /** @var list<array<string, mixed>> $questions */
                    $questions = $lesson['questions'] ?? [];
                    $keptQuestionIds = [];

                    foreach ($questions as $questionIndex => $questionData) {
                        $type = QuestionType::from((string) $questionData['type']);

                        $question = Question::query()->updateOrCreate(
                            [
                                'learning_material_id' => $material->id,
                                'prompt' => (string) $questionData['prompt'],
                            ],
                            [
                                'type' => $type,
                                'explanation' => (string) ($questionData['explanation'] ?? ''),
                                'points' => 10,
                                'order_column' => $questionIndex,
                            ],
                        );

                        $question->forceFill([
                            'audio_path' => 'audio/grade'.self::GRADE_LEVEL.'/q_'.$question->id.'_text.mp3',
                        ])->save();

                        $keptQuestionIds[] = $question->id;

                        /** @var list<array{text: string, correct: bool}> $options */
                        $options = $questionData['options'] ?? [];
                        $keptOptionOrders = [];

                        foreach ($options as $optionIndex => $option) {
                            $questionOption = QuestionOption::query()->updateOrCreate(
                                [
                                    'question_id' => $question->id,
                                    'order_column' => $optionIndex,
                                ],
                                [
                                    'option_text' => (string) $option['text'],
                                    'is_correct' => (bool) $option['correct'],
                                ],
                            );

                            $questionOption->forceFill([
                                'audio_path' => 'audio/grade'.self::GRADE_LEVEL.'/opt_'.$questionOption->id.'.mp3',
                            ])->save();

                            $keptOptionOrders[] = $optionIndex;
                        }

                        if ($keptOptionOrders !== []) {
                            QuestionOption::query()
                                ->where('question_id', $question->id)
                                ->whereNotIn('order_column', $keptOptionOrders)
                                ->delete();
                        }
                    }

                    Question::query()
                        ->where('learning_material_id', $material->id)
                        ->when(
                            $keptQuestionIds !== [],
                            fn ($query) => $query->whereNotIn('id', $keptQuestionIds),
                            fn ($query) => $query,
                        )
                        ->delete();
                }
            }
        }

        $this->call(LetterRaaInteractiveLessonSeeder::class);
        $this->call(NumberThreeInteractiveLessonSeeder::class);
        $this->call(IslamicStudiesInteractiveLessonSeeder::class);
        $this->call(NationalEducationInteractiveLessonSeeder::class);
        $this->call(MasteryConceptSeeder::class);
    }
}
