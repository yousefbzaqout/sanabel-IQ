<?php

declare(strict_types=1);

namespace App\Support\Curriculum\Pipeline;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class CurriculumTextbookDownloader
{
    /**
     * @return string Relative path under storage/curriculum (e.g. palestine/grade_1/semester_1/math.pdf)
     */
    public function download(int $grade, int $semester, string $subject, string $url): string
    {
        $this->assertScope($grade, $semester, $subject);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid textbook URL: {$url}");
        }

        $relative = $this->relativePath($grade, $semester, $subject);
        $absolute = storage_path('curriculum/'.$relative);
        File::ensureDirectoryExists(dirname($absolute));

        $response = Http::timeout(300)
            ->withHeaders(['User-Agent' => 'SanabelIQ-CurriculumPipeline/1.0'])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to download textbook ({$response->status()}): {$url}");
        }

        $body = $response->body();
        if ($body === '') {
            throw new RuntimeException("Empty response body for textbook URL: {$url}");
        }

        File::put($absolute, $body);

        return $relative;
    }

    public function relativePath(int $grade, int $semester, string $subject): string
    {
        $subject = strtolower($subject);

        return "palestine/grade_{$grade}/semester_{$semester}/{$subject}.pdf";
    }

    public function absolutePath(int $grade, int $semester, string $subject): string
    {
        return storage_path('curriculum/'.$this->relativePath($grade, $semester, $subject));
    }

    private function assertScope(int $grade, int $semester, string $subject): void
    {
        if ($grade < 1 || $grade > 6) {
            throw new InvalidArgumentException('Grade must be between 1 and 6.');
        }

        if ($semester < 1 || $semester > 2) {
            throw new InvalidArgumentException('Semester must be 1 or 2.');
        }

        $subject = strtolower($subject);
        if (! in_array($subject, ['arabic', 'math', 'science'], true)) {
            throw new InvalidArgumentException('Subject must be arabic, math, or science.');
        }
    }
}
