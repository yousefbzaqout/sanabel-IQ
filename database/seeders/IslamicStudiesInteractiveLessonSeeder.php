<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Lessons\CurriculumInteractiveLessonImporter;
use App\Support\Lessons\SurahFatihaLessonDefinition;
use Illuminate\Database\Seeder;

class IslamicStudiesInteractiveLessonSeeder extends Seeder
{
    public function run(): void
    {
        app(CurriculumInteractiveLessonImporter::class)->import(
            SurahFatihaLessonDefinition::definition(),
            SurahFatihaLessonDefinition::class,
        );
    }
}
