<?php

declare(strict_types=1);

namespace App\Services\Audio;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class StationAudioGenerator
{
    public function __construct(
        private readonly ArabicTtsSynthesizer $tts = new ArabicTtsSynthesizer,
    ) {}

    /**
     * @return array{generated: int, skipped: int, failed: int}
     */
    public function generate(?string $lessonKey = null, bool $force = false, ?callable $logger = null): array
    {
        $log = $logger ?? static fn (string $message): null => null;

        $query = InteractiveLesson::query()->with('stations');

        if (filled($lessonKey)) {
            $query->where(function ($builder) use ($lessonKey): void {
                $builder->where('lesson_key', $lessonKey)
                    ->orWhere('meta->legacy_key', $lessonKey);
            });
        }

        $lessons = $query->orderBy('id')->get();
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($lessons as $lesson) {
            foreach ($lesson->stations as $station) {
                $result = $this->processStation($lesson, $station, $force, $log);
                $generated += $result['generated'];
                $skipped += $result['skipped'];
                $failed += $result['failed'];
            }
        }

        return compact('generated', 'skipped', 'failed');
    }

    /**
     * @param  callable(string): mixed  $log
     * @return array{generated: int, skipped: int, failed: int}
     */
    private function processStation(
        InteractiveLesson $lesson,
        InteractiveLessonStation $station,
        bool $force,
        callable $log,
    ): array {
        /** @var array<string, mixed> $config */
        $config = is_array($station->config) ? $station->config : [];
        $generated = 0;
        $skipped = 0;
        $failed = 0;
        $changed = false;

        $walk = function (mixed &$node, string $path) use (
            &$walk,
            &$generated,
            &$skipped,
            &$failed,
            &$changed,
            $lesson,
            $station,
            $force,
            $log,
        ): void {
            if (! is_array($node)) {
                return;
            }

            foreach ($node as $key => &$value) {
                if (is_array($value)) {
                    $walk($value, $path.'.'.$key);

                    continue;
                }

                if (! is_string($key) || ! $this->isScriptKey($key) || ! is_string($value)) {
                    continue;
                }

                $script = trim($value);
                if ($script === '') {
                    $skipped++;

                    continue;
                }

                $pathKey = $this->pathKeyForScript($key);
                $existingPath = isset($node[$pathKey]) && is_string($node[$pathKey])
                    ? trim($node[$pathKey])
                    : '';

                $relativePath = $existingPath !== ''
                    ? $existingPath
                    : $this->defaultPath($lesson, $station, $path.'.'.$pathKey, $script);

                $disk = Storage::disk('public');
                $exists = $disk->exists($relativePath) && ($disk->size($relativePath) ?? 0) > 64;

                if (! $force && $exists && $existingPath !== '') {
                    $skipped++;

                    continue;
                }

                try {
                    $stored = $this->tts->synthesizeToPublicPath($script, $relativePath, $force || ! $exists);
                    if (($node[$pathKey] ?? null) !== $stored) {
                        $node[$pathKey] = $stored;
                        $changed = true;
                    }
                    $generated++;
                    $log("✓ station {$station->station_number} {$pathKey}: {$stored}");
                } catch (Throwable $exception) {
                    $failed++;
                    $log("✗ station {$station->station_number} {$pathKey}: {$exception->getMessage()}");
                }
            }
            unset($value);
        };

        $walk($config, 'config');

        if ($changed) {
            $station->forceFill(['config' => $config])->save();
        }

        return compact('generated', 'skipped', 'failed');
    }

    private function isScriptKey(string $key): bool
    {
        return $key === 'audio_script'
            || str_ends_with($key, '_script')
            || in_array($key, ['intro_script', 'prompt_script', 'feedback_script'], true);
    }

    private function pathKeyForScript(string $scriptKey): string
    {
        return match ($scriptKey) {
            'audio_script' => 'audio_path',
            'intro_script' => 'intro_audio_path',
            'prompt_script' => 'prompt_audio_path',
            'feedback_script' => 'feedback_audio_path',
            default => str_ends_with($scriptKey, '_audio_script')
                ? substr($scriptKey, 0, -strlen('_script')).'_path'
                : (str_ends_with($scriptKey, '_script')
                    ? substr($scriptKey, 0, -strlen('_script')).'_audio_path'
                    : $scriptKey.'_audio_path'),
        };
    }

    private function defaultPath(
        InteractiveLesson $lesson,
        InteractiveLessonStation $station,
        string $statePath,
        string $script,
    ): string {
        $slug = Str::slug(Str::limit($statePath.'-'.md5($script), 80, ''), '-');
        if ($slug === '') {
            $slug = substr(md5($script), 0, 12);
        }

        return sprintf(
            'audio/grade%d/lessons/%s/stations/%d/%s.mp3',
            (int) $lesson->grade_level,
            $lesson->lesson_key,
            (int) $station->station_number,
            $slug,
        );
    }
}
