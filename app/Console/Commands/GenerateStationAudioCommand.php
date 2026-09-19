<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Audio\StationAudioGenerator;
use Illuminate\Console\Command;

class GenerateStationAudioCommand extends Command
{
    protected $signature = 'curriculum:generate-station-audio
                            {--lesson= : Interactive lesson_key filter (e.g. ar-g1-letter-raa)}
                            {--force : Regenerate MP3 files even if audio_path already exists}';

    protected $description = 'Pre-render neural MP3 narration for interactive lesson station JSONB scripts';

    public function handle(StationAudioGenerator $generator): int
    {
        $lesson = $this->option('lesson');
        $lessonKey = is_string($lesson) && $lesson !== '' ? $lesson : null;
        $force = (bool) $this->option('force');

        $this->info($lessonKey !== null
            ? "Generating station audio for [{$lessonKey}]..."
            : 'Generating station audio for all interactive lessons...');

        $result = $generator->generate(
            $lessonKey,
            $force,
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
}
