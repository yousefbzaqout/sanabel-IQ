<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\Subject;
use App\Support\Curriculum\PalestinianLessonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateMockDemoCurriculumCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $sandboxRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandboxRoot = storage_path('framework/testing/mock-demo-curriculum');
        File::deleteDirectory($this->sandboxRoot);
        File::ensureDirectoryExists($this->sandboxRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sandboxRoot);
        parent::tearDown();
    }

    #[Test]
    public function it_writes_outlines_and_lessons_then_imports_interactive_stations(): void
    {
        $this->artisan('curriculum:generate-mock-demo', [
            '--grades' => '2',
            '--lessons-per-unit' => 2,
            '--path' => $this->sandboxRoot,
        ])->assertSuccessful()
            ->expectsOutputToContain('Wrote')
            ->expectsOutputToContain('Imported');

        foreach (['arabic', 'math', 'science'] as $subject) {
            $outline = $this->sandboxRoot."/grade2/{$subject}/outline.json";
            $this->assertFileExists($outline);

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode((string) File::get($outline), true, 512, JSON_THROW_ON_ERROR);
            $this->assertGreaterThanOrEqual(2, count($decoded['units'] ?? []));

            foreach ($decoded['units'] as $unit) {
                foreach ($unit['lesson_files'] as $relative) {
                    $packPath = $this->sandboxRoot."/grade2/{$subject}/{$relative}";
                    $this->assertFileExists($packPath);
                    /** @var array<string, mixed> $pack */
                    $pack = json_decode((string) File::get($packPath), true, 512, JSON_THROW_ON_ERROR);
                    PalestinianLessonSchema::assertValid($pack);
                }
            }
        }

        $this->assertGreaterThanOrEqual(3, Subject::query()->where('grade_level', 2)->count());
        $lessons = InteractiveLesson::query()->where('grade_level', 2)->get();
        $this->assertGreaterThanOrEqual(12, $lessons->count());

        foreach ($lessons as $lesson) {
            $this->assertSame(6, InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->count());
        }
    }
}
