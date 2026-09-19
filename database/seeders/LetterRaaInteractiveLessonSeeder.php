<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use Illuminate\Database\Seeder;

class LetterRaaInteractiveLessonSeeder extends Seeder
{
    public function run(): void
    {
        app(LetterRaaInteractiveLessonImporter::class)->import();
    }
}
