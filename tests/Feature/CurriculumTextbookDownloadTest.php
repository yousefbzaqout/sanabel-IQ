<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Curriculum\Pipeline\CurriculumTextbookDownloader;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurriculumTextbookDownloadTest extends TestCase
{
    #[Test]
    public function it_downloads_pdf_into_grade_semester_subject_storage_layout(): void
    {
        Http::fake([
            'https://example.test/books/math-g1-s1.pdf' => Http::response('%PDF-1.4 fake-math-book', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $path = app(CurriculumTextbookDownloader::class)->download(
            grade: 1,
            semester: 1,
            subject: 'math',
            url: 'https://example.test/books/math-g1-s1.pdf',
        );

        $this->assertSame(
            'palestine/grade_1/semester_1/math.pdf',
            $path,
        );
        $absolute = storage_path('curriculum/'.$path);
        $this->assertFileExists($absolute);
        $this->assertStringContainsString('%PDF', (string) file_get_contents($absolute));
    }

    #[Test]
    public function download_command_uses_manifest_entries_with_urls(): void
    {
        Http::fake([
            'https://example.test/arabic.pdf' => Http::response('%PDF-1.4 arabic', 200),
        ]);

        $manifest = database_path('data/palestinian_curriculum/download_manifest.json');
        $this->assertFileExists($manifest);

        $this->artisan('curriculum:download-palestine', [
            '--grade' => 1,
            '--semester' => 1,
            '--subject' => 'arabic',
            '--url' => 'https://example.test/arabic.pdf',
        ])->assertSuccessful();

        $this->assertFileExists(storage_path('curriculum/palestine/grade_1/semester_1/arabic.pdf'));
    }
}
