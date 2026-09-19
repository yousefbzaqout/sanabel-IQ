<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Support\Curriculum\Pipeline\CurriculumPipelineReporter;
use Illuminate\Console\Command;

class CurriculumPipelineReportCommand extends Command
{
    protected $signature = 'curriculum:pipeline-report';

    protected $description = 'Summarize downloaded Palestinian PDFs and generated lesson JSON packs';

    public function handle(CurriculumPipelineReporter $reporter): int
    {
        $summary = $reporter->summarize();

        $this->info('Downloaded textbooks');
        if ($summary['downloaded'] === []) {
            $this->line('  (none yet — run curriculum:download-palestine after filling download_manifest.json urls)');
        } else {
            foreach ($summary['downloaded'] as $row) {
                $kb = number_format($row['bytes'] / 1024, 1);
                $this->line("  - {$row['path']} ({$kb} KB)");
            }
        }

        $this->newLine();
        $this->info('Lesson JSON packs');
        if ($summary['lesson_packs'] === []) {
            $this->line('  (none)');
        } else {
            foreach ($summary['lesson_packs'] as $pack) {
                $key = $pack['lesson_key'] ?? '—';
                $this->line("  - [{$pack['subject']}] {$pack['path']} ({$key})");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Manifest: %d entries, %d with URL',
            $summary['manifest_entries'],
            $summary['manifest_with_url'],
        ));

        return self::SUCCESS;
    }
}
