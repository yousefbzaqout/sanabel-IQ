<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class GradeSubjectSeeder extends Seeder
{
    /**
     * @var list<array{name: string, code: string, slug: string}>
     */
    private const SUBJECT_TEMPLATES = [
        ['name' => 'Math', 'code' => 'MATH', 'slug' => 'math'],
        ['name' => 'Science', 'code' => 'SCI', 'slug' => 'science'],
        ['name' => 'Arabic', 'code' => 'AR', 'slug' => 'arabic'],
    ];

    public function run(): void
    {
        for ($gradeLevel = 1; $gradeLevel <= 5; $gradeLevel++) {
            foreach (self::SUBJECT_TEMPLATES as $template) {
                Subject::query()->updateOrCreate(
                    [
                        'code' => $template['code'],
                        'grade_level' => $gradeLevel,
                    ],
                    [
                        'name' => $template['name'],
                        'slug' => $template['slug'].'-g'.$gradeLevel,
                    ],
                );
            }
        }
    }
}
