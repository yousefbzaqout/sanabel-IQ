<?php

declare(strict_types=1);

namespace App\Services\Audio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class ArabicTtsSynthesizer
{
    public function __construct(
        private readonly string $disk = 'public',
    ) {}

    /**
     * Synthesize Arabic speech to an MP3 on the public disk.
     *
     * Applies تشكيل (diacritics) before synthesis so Grade-1 narration is clearer
     * (unless $withDiacritics is false — used for interactive live TTS latency).
     * Prefers Microsoft Edge neural TTS; falls back to Google Translate TTS.
     *
     * @return string Relative path on the public disk
     */
    public function synthesizeToPublicPath(
        string $text,
        string $relativePath,
        bool $force = false,
        bool $withDiacritics = true,
    ): string {
        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            throw new RuntimeException('Cannot synthesize empty speech text.');
        }

        $disk = Storage::disk($this->disk);

        if (! $force && $disk->exists($relativePath) && ($disk->size($relativePath) ?? 0) > 64) {
            return $relativePath;
        }

        $forSpeech = $withDiacritics ? $this->withDiacritics($normalized) : $normalized;
        $audio = $this->fetchMp3($forSpeech);
        $directory = dirname($relativePath);

        if ($directory !== '.' && $directory !== '') {
            $disk->makeDirectory($directory);
        }

        $disk->put($relativePath, $audio);

        return $relativePath;
    }

    public function normalizeText(string $text): string
    {
        // Strip emoji / decorative symbols so Grade-1 narration stays clear.
        $withoutEmoji = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text) ?? $text;
        $collapsed = preg_replace('/\s+/u', ' ', $withoutEmoji) ?? $withoutEmoji;

        return trim($collapsed);
    }

    /**
     * Add Arabic diacritics (تشكيل) for clearer TTS pronunciation.
     */
    public function withDiacritics(string $text): string
    {
        $normalized = $this->normalizeText($text);

        if ($normalized === '' || $this->alreadyHasDiacritics($normalized)) {
            return $normalized;
        }

        try {
            $response = Http::timeout(12)
                ->asForm()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; SanabelIQ-CurriculumTTS/1.0)',
                    'Accept' => 'application/json,text/plain,*/*',
                ])
                ->post('https://tahadz.com/mishkal/ajaxGet', [
                    'text' => $normalized,
                    'action' => 'TashkeelText',
                ]);

            if (! $response->successful()) {
                Log::warning('Arabic diacritizer upstream failed', [
                    'status' => $response->status(),
                    'preview' => mb_substr($normalized, 0, 48),
                ]);

                return $normalized;
            }

            /** @var array{result?: string}|string $payload */
            $payload = $response->json() ?? $response->body();
            $result = is_array($payload)
                ? trim((string) ($payload['result'] ?? ''))
                : trim((string) $payload);

            return $result !== '' ? $result : $normalized;
        } catch (\Throwable $exception) {
            Log::warning('Arabic diacritizer exception', [
                'message' => $exception->getMessage(),
                'preview' => mb_substr($normalized, 0, 48),
            ]);

            return $normalized;
        }
    }

    private function alreadyHasDiacritics(string $text): bool
    {
        return (bool) preg_match('/[\x{064B}-\x{0652}]/u', $text);
    }

    private function fetchMp3(string $text): string
    {
        $driver = (string) config('services.tts.driver', 'edge');

        if ($driver === 'edge') {
            try {
                return $this->fetchEdgeNeuralMp3($text);
            } catch (\Throwable $exception) {
                Log::warning('Edge neural TTS failed — falling back to Google TTS', [
                    'message' => $exception->getMessage(),
                    'preview' => mb_substr($text, 0, 48),
                ]);
            }
        }

        return $this->fetchGoogleMp3($text);
    }

    /**
     * Microsoft Edge neural Arabic voice (no Azure API key).
     */
    private function fetchEdgeNeuralMp3(string $text): string
    {
        $python = (string) config('services.tts.python', 'python3');
        $voice = (string) config('services.tts.voice', 'ar-SA-ZariyahNeural');
        $rate = (string) config('services.tts.rate', '-15%');
        $tmpPath = tempnam(sys_get_temp_dir(), 'sanabel_tts_');

        if ($tmpPath === false) {
            throw new RuntimeException('Unable to allocate temp file for neural TTS.');
        }

        $mp3Path = $tmpPath.'.mp3';
        $textPath = $tmpPath.'.txt';
        @unlink($tmpPath);

        try {
            if (file_put_contents($textPath, $text) === false) {
                throw new RuntimeException('Unable to write temp text for neural TTS.');
            }

            // Use --rate=VALUE (single argv) so negative rates are not parsed as flags.
            // Pass text via --file to avoid shell/arg encoding issues with Arabic.
            $process = new Process([
                $python,
                '-m',
                'edge_tts',
                '--voice='.$voice,
                '--rate='.$rate,
                '--file='.$textPath,
                '--write-media='.$mp3Path,
            ]);
            $process->setTimeout(45);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'edge_tts failed');
            }

            if (! is_file($mp3Path)) {
                throw new RuntimeException('edge_tts did not write an MP3 file.');
            }

            $audio = file_get_contents($mp3Path);

            if ($audio === false || $audio === '' || strlen($audio) < 64) {
                throw new RuntimeException('edge_tts returned empty audio.');
            }

            return $audio;
        } finally {
            if (is_file($mp3Path)) {
                @unlink($mp3Path);
            }
            if (is_file($textPath)) {
                @unlink($textPath);
            }
        }
    }

    private function fetchGoogleMp3(string $text): string
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; SanabelIQ-CurriculumTTS/1.0)',
                'Accept' => '*/*',
            ])
            ->get('https://translate.google.com/translate_tts', [
                'ie' => 'UTF-8',
                'client' => 'gtx',
                'tl' => 'ar',
                'q' => $text,
            ]);

        if (! $response->successful()) {
            Log::warning('Arabic TTS upstream failed', [
                'status' => $response->status(),
                'preview' => mb_substr($text, 0, 48),
            ]);

            throw new RuntimeException('Arabic TTS upstream failed with HTTP '.$response->status());
        }

        $audio = $response->body();

        if ($audio === '' || strlen($audio) < 64) {
            throw new RuntimeException('Arabic TTS returned empty audio.');
        }

        return $audio;
    }
}
