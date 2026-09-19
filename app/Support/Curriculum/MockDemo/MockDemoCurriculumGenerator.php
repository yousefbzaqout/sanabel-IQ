<?php

declare(strict_types=1);

namespace App\Support\Curriculum\MockDemo;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Writes mock demo outlines + lesson packs under palestinian_curriculum/grade{N}/.
 */
final class MockDemoCurriculumGenerator
{
    public function __construct(
        private readonly MockDemoTopicCatalog $catalog = new MockDemoTopicCatalog,
        private readonly MockDemoLessonPackFactory $factory = new MockDemoLessonPackFactory,
    ) {}

    /**
     * @param  list<int>  $grades
     * @return array{files_written: int, outlines: list<string>, lesson_keys: list<string>}
     */
    public function generate(array $grades, int $lessonsPerUnit, string $rootPath, bool $force = true): array
    {
        $filesWritten = 0;
        $outlines = [];
        $lessonKeys = [];

        foreach ($grades as $grade) {
            foreach (['arabic', 'math', 'science'] as $subject) {
                $result = $this->generateSubject($grade, $subject, $lessonsPerUnit, $rootPath, $force);
                $filesWritten += $result['files_written'];
                $outlines[] = $result['outline'];
                $lessonKeys = array_merge($lessonKeys, $result['lesson_keys']);
            }
        }

        return [
            'files_written' => $filesWritten,
            'outlines' => $outlines,
            'lesson_keys' => $lessonKeys,
        ];
    }

    /**
     * @return array{files_written: int, outline: string, lesson_keys: list<string>}
     */
    private function generateSubject(
        int $grade,
        string $subject,
        int $lessonsPerUnit,
        string $rootPath,
        bool $force,
    ): array {
        $subjectDir = rtrim($rootPath, '/').'/grade'.$grade.'/'.$subject;
        $lessonsDir = $subjectDir.'/lessons';
        File::ensureDirectoryExists($lessonsDir);

        $meta = $this->factory->subjectMeta($subject);
        $preservedUnits = $this->preservedUnits($subjectDir.'/outline.json');
        $mockUnits = [];
        $lessonKeys = [];
        $filesWritten = 0;
        $order = 10;

        foreach ([1, 2] as $semester) {
            $topics = $this->catalog->topicsFor($grade, $subject, $semester);
            $selected = array_slice($topics, 0, max(1, $lessonsPerUnit));
            $unitTitle = 'تجريبي: '.(string) ($selected[0]['unit_title'] ?? "الفصل {$semester}");
            $lessonFiles = [];

            foreach ($selected as $index => $topic) {
                $pack = $this->factory->make(
                    grade: $grade,
                    semester: $semester,
                    subject: $subject,
                    topic: $topic,
                    lessonIndex: $index,
                    orderColumn: $order,
                );
                $order += 10;

                $fileName = 'mock-'.Str::slug((string) $topic['slug']).'.json';
                if ($fileName === 'mock-.json') {
                    $fileName = 'mock-lesson-'.$index.'.json';
                }
                $relative = 'lessons/'.$fileName;
                $absolute = $subjectDir.'/'.$relative;

                if ($force || ! is_file($absolute)) {
                    File::put(
                        $absolute,
                        json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n",
                    );
                    $filesWritten++;
                }

                $lessonFiles[] = $relative;
                $lessonKeys[] = (string) $pack['lesson_key'];
            }

            $mockUnits[] = [
                'order' => 100 + $semester,
                'title' => $unitTitle,
                'semester' => $semester,
                'lesson_files' => $lessonFiles,
            ];
        }

        $units = array_values(array_merge($preservedUnits, $mockUnits));
        foreach ($units as $i => $unit) {
            $units[$i]['order'] = $i + 1;
        }

        $existingSubject = $this->existingSubjectMeta($subjectDir.'/outline.json');
        $outline = [
            'schema_version' => '1.0.0',
            'grade_level' => $grade,
            'semester' => 1,
            'subject' => $existingSubject ?? [
                'code' => $meta['code'],
                'name' => $meta['name'],
                'slug' => sprintf('%s-g%d-palestinian', $subject, $grade),
                'icon' => $meta['icon'],
                'description' => sprintf(
                    '%s للصف %d — منهاج تجريبي للعرض (يشمل وحدات أصلية وتجريبية)',
                    $meta['name'],
                    $grade,
                ),
            ],
            'official_outcomes' => $this->existingOutcomes($subjectDir.'/outline.json') ?? [
                'محتوى غني للعرض على خريطة الطالب',
                'دروس تفاعلية بست محطات مع اختبارات قصيرة',
                'تلميحات سنبل السياقية في كل درس',
            ],
            'units' => $units,
        ];

        $outlinePath = $subjectDir.'/outline.json';
        File::put(
            $outlinePath,
            json_encode($outline, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n",
        );
        $filesWritten++;

        return [
            'files_written' => $filesWritten,
            'outline' => $outlinePath,
            'lesson_keys' => $lessonKeys,
        ];
    }

    /**
     * Keep non-mock units already present in an outline (handcrafted prototype lessons).
     *
     * @return list<array<string, mixed>>
     */
    private function preservedUnits(string $outlinePath): array
    {
        if (! is_file($outlinePath)) {
            return [];
        }

        try {
            /** @var array<string, mixed> $outline */
            $outline = json_decode((string) File::get($outlinePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        $preserved = [];
        $baseDir = dirname($outlinePath);
        foreach ($outline['units'] ?? [] as $unit) {
            if (! is_array($unit)) {
                continue;
            }
            $files = $unit['lesson_files'] ?? [];
            if (! is_array($files) || $files === []) {
                continue;
            }
            $hasOnlyMock = true;
            $allExist = true;
            foreach ($files as $file) {
                if (! is_string($file) || ! str_contains($file, '/mock-')) {
                    $hasOnlyMock = false;
                }
                if (! is_string($file) || ! is_file($baseDir.'/'.$file)) {
                    $allExist = false;
                }
            }
            if ($hasOnlyMock || ! $allExist) {
                continue;
            }
            $preserved[] = $unit;
        }

        return $preserved;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function existingSubjectMeta(string $outlinePath): ?array
    {
        if (! is_file($outlinePath)) {
            return null;
        }
        try {
            /** @var array<string, mixed> $outline */
            $outline = json_decode((string) File::get($outlinePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
        $subject = $outline['subject'] ?? null;
        if (! is_array($subject) || ! isset($subject['code'], $subject['name'], $subject['slug'])) {
            return null;
        }
        // Prefer handcrafted subject metadata over previous mock-demo slugs.
        if (is_string($subject['slug'] ?? null) && str_contains((string) $subject['slug'], 'mock-demo')) {
            return null;
        }

        return $subject;
    }

    /**
     * @return list<string>|null
     */
    private function existingOutcomes(string $outlinePath): ?array
    {
        if (! is_file($outlinePath)) {
            return null;
        }
        try {
            /** @var array<string, mixed> $outline */
            $outline = json_decode((string) File::get($outlinePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
        $outcomes = $outline['official_outcomes'] ?? null;
        if (! is_array($outcomes) || $outcomes === []) {
            return null;
        }
        $joined = implode(' ', array_map('strval', $outcomes));
        if (str_contains($joined, 'تجريبي')) {
            return null;
        }

        return array_values(array_map('strval', $outcomes));
    }
}
