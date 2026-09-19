<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Support\Curriculum\PalestinianCurriculumTransformer;
use App\Support\Curriculum\PalestinianLessonSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Validates / previews Palestinian curriculum JSON packs (Arabic + Math).
 */
class BuildPalestinianCurriculumCommand extends Command
{
    protected $signature = 'curriculum:palestinian
                            {--validate : Validate outline + lesson packs against schema}
                            {--preview= : Preview transformer output for a lesson JSON path}';

    protected $description = 'Validate and preview Palestinian curriculum JSON → Sanabel interactive engine packs';

    public function handle(PalestinianCurriculumTransformer $transformer): int
    {
        if ($this->option('preview')) {
            return $this->preview((string) $this->option('preview'), $transformer);
        }

        return $this->validatePacks();
    }

    private function validatePacks(): int
    {
        $sourcesPath = database_path('data/palestinian_curriculum/sources.json');
        $gradeRoot = database_path('data/palestinian_curriculum/grade1');

        if (! is_file($sourcesPath) || ! is_dir($gradeRoot)) {
            $this->error('Missing sources.json or grade1/ under database/data/palestinian_curriculum');

            return self::FAILURE;
        }

        $ok = 0;
        $failed = 0;
        $subjectDirs = File::directories($gradeRoot);
        sort($subjectDirs);

        foreach ($subjectDirs as $subjectDir) {
            $outlinePath = $subjectDir.'/outline.json';
            if (! is_file($outlinePath)) {
                continue;
            }

            $subjectSlug = basename($subjectDir);
            $this->line("<comment>Subject: {$subjectSlug}</comment>");

            try {
                /** @var array<string, mixed> $outline */
                $outline = json_decode((string) File::get($outlinePath), true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                $this->error("✗ outline.json ({$subjectSlug}): ".$e->getMessage());
                $failed++;

                continue;
            }

            foreach ($outline['units'] ?? [] as $unit) {
                foreach ($unit['lesson_files'] ?? [] as $relative) {
                    $path = $subjectDir.'/'.$relative;
                    $label = $subjectSlug.'/'.$relative;
                    try {
                        /** @var array<string, mixed> $pack */
                        $pack = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
                        PalestinianLessonSchema::assertValid($pack);
                        $this->line("<info>✓</info> {$label}");
                        $ok++;
                    } catch (Throwable $e) {
                        $this->error("✗ {$label}: ".$e->getMessage());
                        $failed++;
                    }
                }
            }
        }

        if ($ok === 0 && $failed === 0) {
            $this->error('No lesson packs found to validate.');

            return self::FAILURE;
        }

        $this->info("Validated {$ok} packs".($failed > 0 ? ", {$failed} failed" : ''));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function preview(string $path, PalestinianCurriculumTransformer $transformer): int
    {
        $absolute = str_starts_with($path, '/') ? $path : base_path($path);
        if (! is_file($absolute)) {
            $this->error("File not found: {$absolute}");

            return self::FAILURE;
        }

        /** @var array<string, mixed> $pack */
        $pack = json_decode((string) File::get($absolute), true, 512, JSON_THROW_ON_ERROR);
        $definition = $transformer->toInteractiveDefinition($pack);

        $this->info('lesson_key: '.$definition['lesson_key']);
        $this->info('subject_code: '.$definition['subject_code']);
        $this->info('content_kind: '.($definition['content_kind'] ?? 'letter'));
        $this->info('voice_targets: '.implode(', ', array_column($definition['variant_tabs'], 'glyph')));
        $this->info('stroke path length: '.strlen((string) $definition['tracing']['path']));
        $this->info('quiz questions: '.count($definition['quiz']['questions'] ?? []));
        $this->line(json_encode([
            'voice_targets' => array_column($definition['variant_tabs'], 'glyph'),
            'structure_mode' => $definition['structure']['mode'] ?? null,
            'stroke' => $definition['tracing'],
            'sonbol_station_1' => $definition['station_copy'][1]['sonbol_prompt'] ?? null,
            'quiz_count' => count($definition['quiz']['questions'] ?? []),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}');

        return self::SUCCESS;
    }
}
