<?php

declare(strict_types=1);

namespace App\Support\Curriculum\Pipeline;

use Illuminate\Support\Facades\File;

final class CurriculumPipelineReporter
{
    /**
     * @return array{
     *     downloaded: list<array{path: string, bytes: int}>,
     *     lesson_packs: list<array{path: string, lesson_key: string|null, subject: string}>,
     *     manifest_entries: int,
     *     manifest_with_url: int
     * }
     */
    public function summarize(): array
    {
        $downloaded = [];
        $root = storage_path('curriculum/palestine');
        if (is_dir($root)) {
            foreach (File::allFiles($root) as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') {
                    continue;
                }
                $downloaded[] = [
                    'path' => str_replace(storage_path('curriculum').DIRECTORY_SEPARATOR, '', $file->getPathname()),
                    'bytes' => $file->getSize(),
                ];
            }
        }

        $lessonPacks = [];
        $dataRoot = database_path('data/palestinian_curriculum');
        if (is_dir($dataRoot)) {
            foreach (File::allFiles($dataRoot) as $file) {
                if ($file->getExtension() !== 'json') {
                    continue;
                }
                if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'lessons'.DIRECTORY_SEPARATOR)) {
                    continue;
                }
                $lessonKey = null;
                try {
                    /** @var array<string, mixed> $decoded */
                    $decoded = json_decode($file->getContents(), true, 512, JSON_THROW_ON_ERROR);
                    $lessonKey = isset($decoded['lesson_key']) ? (string) $decoded['lesson_key'] : null;
                } catch (\Throwable) {
                    $lessonKey = null;
                }

                $relative = str_replace(database_path('data/palestinian_curriculum').DIRECTORY_SEPARATOR, '', $file->getPathname());
                $subject = 'unknown';
                if (preg_match('#grade\d+/([a-z]+)/lessons#', str_replace('\\', '/', $relative), $m) === 1) {
                    $subject = $m[1];
                }

                $lessonPacks[] = [
                    'path' => $relative,
                    'lesson_key' => $lessonKey,
                    'subject' => $subject,
                ];
            }
        }

        $manifestPath = database_path('data/palestinian_curriculum/download_manifest.json');
        $manifestEntries = 0;
        $manifestWithUrl = 0;
        if (is_file($manifestPath)) {
            /** @var array<string, mixed> $manifest */
            $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $entries = $manifest['entries'] ?? [];
            $manifestEntries = is_array($entries) ? count($entries) : 0;
            foreach ($entries as $entry) {
                if (is_array($entry) && ! empty($entry['url'])) {
                    $manifestWithUrl++;
                }
            }
        }

        return [
            'downloaded' => $downloaded,
            'lesson_packs' => $lessonPacks,
            'manifest_entries' => $manifestEntries,
            'manifest_with_url' => $manifestWithUrl,
        ];
    }
}
