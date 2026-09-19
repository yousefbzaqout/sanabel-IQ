<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Curriculum\PalestinianCurriculumJsonImporter;
use Illuminate\Database\Seeder;

/**
 * Palestinian Grade 1 curriculum prototype:
 * - Arabic Unit 1 letters (ر / د / ب)
 * - Math Unit: الأعداد حتى 9 (packs 1–3, 4–6, 7–9)
 *
 * Loaded from structured JSON under database/data/palestinian_curriculum.
 * Sources: وزارة التربية والتعليم الفلسطينية / مركز المناهج
 * (see database/data/palestinian_curriculum/sources.json).
 */
class PalestinianCurriculumPrototypeSeeder extends Seeder
{
    public function run(): void
    {
        $keys = app(PalestinianCurriculumJsonImporter::class)->importPrototype();

        $this->command?->info('Palestinian curriculum imported: '.implode(', ', $keys));
    }
}
