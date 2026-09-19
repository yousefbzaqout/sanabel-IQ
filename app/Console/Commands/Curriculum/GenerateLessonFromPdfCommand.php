<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Services\AI\PalestinianLessonGeneratorService;
use App\Support\Curriculum\Pipeline\CurriculumPdfChunker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class GenerateLessonFromPdfCommand extends Command
{
    protected $signature = 'curriculum:generate-from-pdf
                            {filePath : Absolute path or path relative to storage/curriculum}
                            {grade : Grade level 1-6}
                            {subject : arabic|math|science}
                            {--semester=1 : Semester 1 or 2}
                            {--limit=0 : Max chunks to generate (0 = all)}
                            {--dry-run : Parse and show chunks without calling the LLM}
                            {--import : After saving JSON, run PalestinianCurriculumJsonImporter for this subject outline}';

    protected $description = 'Parse a Palestinian textbook PDF chunk and generate Sanabel-IQ lesson JSON via Prism';

    public function handle(
        CurriculumPdfChunker $chunker,
        PalestinianLessonGeneratorService $generator,
    ): int {
        $filePath = (string) $this->argument('filePath');
        $grade = (int) $this->argument('grade');
        $subject = strtolower((string) $this->argument('subject'));
        $semester = (int) $this->option('semester');
        $limit = (int) $this->option('limit');

        try {
            $chunks = $chunker->chunkFromPdf($filePath);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($chunks === []) {
            $this->error('No chunks extracted from PDF.');

            return self::FAILURE;
        }

        $this->info('Extracted '.count($chunks).' chunk(s).');

        if ($this->option('dry-run')) {
            foreach ($chunks as $chunk) {
                $this->line(sprintf(
                    '#%d %s (%d chars)',
                    $chunk['index'],
                    $chunk['title'],
                    mb_strlen($chunk['content']),
                ));
            }

            return self::SUCCESS;
        }

        $folder = match ($subject) {
            'arabic', 'ar' => 'arabic',
            'math', 'mathematics' => 'math',
            'science', 'sci' => 'science',
            default => $subject,
        };

        $outDir = database_path("data/palestinian_curriculum/grade{$grade}/{$folder}/lessons");
        File::ensureDirectoryExists($outDir);

        $generated = 0;
        $failed = 0;
        $targets = $limit > 0 ? array_slice($chunks, 0, $limit) : $chunks;

        foreach ($targets as $chunk) {
            $this->line("Generating: {$chunk['title']}");
            try {
                $pack = $generator->generateFromChunk($chunk, $grade, $subject, $semester);
                $filename = Str::slug((string) ($pack['key'] ?? 'lesson-'.$chunk['index']), '-');
                if ($filename === '') {
                    $filename = 'lesson-'.$chunk['index'];
                }
                // Preserve ai-generated style keys that may already be slug-like
                if (isset($pack['key']) && is_string($pack['key']) && preg_match('/^[a-z0-9\-]+$/', $pack['key']) === 1) {
                    $filename = $pack['key'];
                }

                $path = $outDir.'/'.$filename.'.json';
                File::put(
                    $path,
                    json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n",
                );
                $this->info("✓ {$path}");
                $generated++;
            } catch (Throwable $e) {
                $this->error('✗ '.$e->getMessage());
                $failed++;
            }
        }

        $this->info("Generated {$generated} pack(s), failed {$failed}");

        if ($this->option('import') && $generated > 0) {
            $this->call('curriculum:palestinian', ['--validate' => true]);
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\PalestinianCurriculumPrototypeSeeder']);
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
