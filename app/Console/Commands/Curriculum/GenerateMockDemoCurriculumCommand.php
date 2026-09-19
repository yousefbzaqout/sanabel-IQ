<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Support\Curriculum\MockDemo\MockDemoCurriculumGenerator;
use App\Support\Curriculum\PalestinianCurriculumJsonImporter;
use Illuminate\Console\Command;
use Throwable;

class GenerateMockDemoCurriculumCommand extends Command
{
    protected $signature = 'curriculum:generate-mock-demo
                            {--grades=1,2,3,4,5,6 : Comma-separated grade list}
                            {--lessons-per-unit=3 : Lessons generated per semester unit}
                            {--path= : Override curriculum root (defaults to database/data/palestinian_curriculum)}
                            {--no-import : Write JSON only; skip database import}
                            {--force : Overwrite existing mock lesson JSON files}';

    protected $description = 'Generate realistic mock Palestinian curriculum JSON (grades 1–6) for marketing/demo and optionally import it';

    public function handle(
        MockDemoCurriculumGenerator $generator,
        PalestinianCurriculumJsonImporter $importer,
    ): int {
        $grades = $this->parseGrades((string) $this->option('grades'));
        $lessonsPerUnit = max(1, min(6, (int) $this->option('lessons-per-unit')));
        $root = (string) ($this->option('path') ?: database_path('data/palestinian_curriculum'));
        $force = (bool) $this->option('force') || $this->option('path') !== null;

        try {
            $result = $generator->generate($grades, $lessonsPerUnit, $root, $force);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Wrote %d files across grades %s (%d lesson keys).',
            $result['files_written'],
            implode(',', $grades),
            count($result['lesson_keys']),
        ));

        if ($this->option('no-import')) {
            $this->comment('Skipped database import (--no-import).');

            return self::SUCCESS;
        }

        $imported = [];
        foreach ($grades as $grade) {
            try {
                $imported = array_merge($imported, $importer->importGrade($grade, $root));
            } catch (Throwable $e) {
                $this->error("Import failed for grade {$grade}: ".$e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info('Imported '.count($imported).' interactive lesson(s) via CurriculumInteractiveLessonImporter.');

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function parseGrades(string $raw): array
    {
        $grades = [];
        foreach (explode(',', $raw) as $part) {
            $grade = (int) trim($part);
            if ($grade >= 1 && $grade <= 6) {
                $grades[] = $grade;
            }
        }

        $grades = array_values(array_unique($grades));
        sort($grades);

        if ($grades === []) {
            throw new \InvalidArgumentException('Provide at least one grade between 1 and 6.');
        }

        return $grades;
    }
}
