<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Support\Curriculum\Pipeline\CurriculumTextbookDownloader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class DownloadPalestinianTextbooksCommand extends Command
{
    protected $signature = 'curriculum:download-palestine
                            {--grade= : Limit to a grade 1-6}
                            {--semester= : Limit to semester 1 or 2}
                            {--subject= : arabic|math|science}
                            {--url= : Override download URL for a single book (requires grade, semester, subject)}';

    protected $description = 'Download Palestinian textbooks into storage/curriculum/palestine/grade_X/semester_Y/{subject}.pdf';

    public function handle(CurriculumTextbookDownloader $downloader): int
    {
        $urlOverride = $this->option('url');
        if (is_string($urlOverride) && $urlOverride !== '') {
            return $this->downloadOne($downloader, $urlOverride);
        }

        $manifestPath = database_path('data/palestinian_curriculum/download_manifest.json');
        if (! is_file($manifestPath)) {
            $this->error('Missing download_manifest.json');

            return self::FAILURE;
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $gradeFilter = $this->option('grade') !== null ? (int) $this->option('grade') : null;
        $semesterFilter = $this->option('semester') !== null ? (int) $this->option('semester') : null;
        $subjectFilter = $this->option('subject') !== null ? strtolower((string) $this->option('subject')) : null;

        $ok = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($manifest['entries'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $grade = (int) ($entry['grade'] ?? 0);
            $semester = (int) ($entry['semester'] ?? 0);
            $subject = strtolower((string) ($entry['subject'] ?? ''));
            $url = $entry['url'] ?? null;

            if ($gradeFilter !== null && $grade !== $gradeFilter) {
                continue;
            }
            if ($semesterFilter !== null && $semester !== $semesterFilter) {
                continue;
            }
            if ($subjectFilter !== null && $subject !== $subjectFilter) {
                continue;
            }

            if (! is_string($url) || $url === '') {
                $this->warn("Skip (no url): grade {$grade} semester {$semester} {$subject}");
                $skipped++;

                continue;
            }

            try {
                $path = $downloader->download($grade, $semester, $subject, $url);
                $this->info("✓ {$path}");
                $ok++;
            } catch (Throwable $e) {
                $this->error("✗ grade {$grade} {$subject}: ".$e->getMessage());
                $failed++;
            }
        }

        $this->info("Downloaded {$ok}, skipped {$skipped}, failed {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function downloadOne(CurriculumTextbookDownloader $downloader, string $url): int
    {
        $grade = (int) $this->option('grade');
        $semester = (int) ($this->option('semester') ?: 1);
        $subject = strtolower((string) $this->option('subject'));

        if ($grade < 1 || $subject === '') {
            $this->error('When using --url you must pass --grade and --subject (and optionally --semester).');

            return self::FAILURE;
        }

        try {
            $path = $downloader->download($grade, $semester, $subject, $url);
            $this->info("Saved: storage/curriculum/{$path}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
