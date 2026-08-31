<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Math', 'slug' => 'math'],
            ['name' => 'Science', 'slug' => 'science'],
            ['name' => 'Arabic', 'slug' => 'arabic'],
        ];

        foreach ($subjects as $subject) {
            Subject::query()->updateOrCreate(['slug' => $subject['slug']], $subject);
        }
    }
}
