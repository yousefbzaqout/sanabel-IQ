<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Lessons\CurriculumInteractiveLessonImporter;
use App\Support\Lessons\PalestineFlagLessonDefinition;
use Illuminate\Database\Seeder;

class NationalEducationInteractiveLessonSeeder extends Seeder
{
    public function run(): void
    {
        app(CurriculumInteractiveLessonImporter::class)->import(
            PalestineFlagLessonDefinition::definition(),
            PalestineFlagLessonDefinition::class,
        );
    }
}
