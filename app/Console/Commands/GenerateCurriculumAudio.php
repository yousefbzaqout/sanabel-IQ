<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Services\Audio\ArabicTtsSynthesizer;
use App\Services\Audio\StationAudioGenerator;
use Illuminate\Console\Command;
use Throwable;

class GenerateCurriculumAudio extends Command
{
    protected $signature = 'curriculum:generate-audio
                            {--grade=1 : Grade level to generate quiz audio for}
                            {--lesson= : Interactive lesson_key — generate station JSONB audio instead of quiz MP3s}
                            {--force : Regenerate MP3 files even if they already exist}';

    protected $description = 'Pre-render Arabic MP3 narration for Grade curriculum quizzes or interactive lesson stations';

    public function handle(ArabicTtsSynthesizer $tts, StationAudioGenerator $stationAudio): int
    {
        $lesson = $this->option('lesson');

        if (is_string($lesson) && $lesson !== '') {
            $this->info("Delegating to station audio pipeline for [{$lesson}]...");

            $result = $stationAudio->generate(
                $lesson,
                (bool) $this->option('force'),
                fn (string $message) => $this->line($message),
            );

            $this->info(sprintf(
                'Station audio complete: %d generated, %d skipped, %d failed.',
                $result['generated'],
                $result['skipped'],
                $result['failed'],
            ));

            return $result['failed'] > 0 && $result['generated'] === 0
                ? self::FAILURE
                : self::SUCCESS;
        }

        $grade = (int) $this->option('grade');
        $force = (bool) $this->option('force');

        $subjects = Subject::query()->where('grade_level', $grade)->orderBy('id')->get();

        if ($subjects->isEmpty()) {
            $this->warn("No subjects found for grade {$grade}. Seed curriculum first.");

            return self::FAILURE;
        }

        $generated = 0;
        $failed = 0;

        foreach ($subjects as $subject) {
            $materials = LearningMaterial::query()
                ->where('subject_id', $subject->id)
                ->with(['questions.options'])
                ->orderBy('order_column')
                ->get();

            foreach ($materials as $material) {
                if ($this->synthesize(
                    $tts,
                    $material->title,
                    "audio/grade{$grade}/material_{$material->id}_title.mp3",
                    $force,
                    fn (string $path) => $material->forceFill(['audio_path' => $path])->save(),
                )) {
                    $generated++;
                } else {
                    $failed++;
                }

                foreach ($material->questions as $question) {
                    if ($this->synthesize(
                        $tts,
                        $question->prompt,
                        "audio/grade{$grade}/q_{$question->id}_text.mp3",
                        $force,
                        fn (string $path) => $question->forceFill(['audio_path' => $path])->save(),
                    )) {
                        $generated++;
                    } else {
                        $failed++;
                    }

                    foreach ($question->options as $option) {
                        if ($this->synthesize(
                            $tts,
                            $option->option_text,
                            "audio/grade{$grade}/opt_{$option->id}.mp3",
                            $force,
                            fn (string $path) => $option->forceFill(['audio_path' => $path])->save(),
                        )) {
                            $generated++;
                        } else {
                            $failed++;
                        }
                    }
                }
            }
        }

        $this->info("Curriculum audio complete for grade {$grade}: {$generated} generated, {$failed} failed.");

        return $failed > 0 && $generated === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  callable(string): void  $onSuccess
     */
    private function synthesize(
        ArabicTtsSynthesizer $tts,
        string $text,
        string $path,
        bool $force,
        callable $onSuccess,
    ): bool {
        try {
            $stored = $tts->synthesizeToPublicPath($text, $path, $force);
            $onSuccess($stored);
            $this->line("✓ {$stored}");

            return true;
        } catch (Throwable $exception) {
            $this->error("✗ {$path}: {$exception->getMessage()}");

            return false;
        }
    }
}
