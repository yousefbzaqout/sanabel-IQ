<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SimulationCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SubjectSeeder::class);

        $subject = Subject::query()->where('slug', 'math')->firstOrFail();

        $material = LearningMaterial::query()->updateOrCreate(
            [
                'subject_id' => $subject->id,
                'title' => 'اختبار محاكاة - جمع الأعداد',
            ],
            [
                'description' => 'Material seeded for local E2E simulation.',
                'xp_reward' => 50,
                'order_column' => 0,
                'is_published' => true,
            ],
        );

        $question = Question::query()->updateOrCreate(
            [
                'learning_material_id' => $material->id,
                'prompt' => 'ما حاصل 2 + 3؟',
            ],
            [
                'type' => QuestionType::Mcq,
                'points' => 10,
                'order_column' => 0,
                'explanation' => '2 + 3 = 5',
            ],
        );

        QuestionOption::query()->updateOrCreate(
            ['question_id' => $question->id, 'order_column' => 0],
            ['option_text' => '4', 'is_correct' => false],
        );

        QuestionOption::query()->updateOrCreate(
            ['question_id' => $question->id, 'order_column' => 1],
            ['option_text' => '5', 'is_correct' => true],
        );
    }
}
