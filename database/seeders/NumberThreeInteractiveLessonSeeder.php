<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Lessons\NumberThreeInteractiveLessonImporter;
use Illuminate\Database\Seeder;

class NumberThreeInteractiveLessonSeeder extends Seeder
{
    public function run(): void
    {
        app(NumberThreeInteractiveLessonImporter::class)->import();
    }
}
