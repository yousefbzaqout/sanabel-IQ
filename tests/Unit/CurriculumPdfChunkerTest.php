<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Curriculum\Pipeline\CurriculumPdfChunker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurriculumPdfChunkerTest extends TestCase
{
    #[Test]
    public function it_splits_arabic_textbook_text_by_units_and_lessons(): void
    {
        $text = <<<'AR'
كتاب الرياضيات الصف الأول
الوحدة الأولى: الأعداد حتى 9
مقدمة قصيرة عن العد.
الدرس 1: الأعداد من 1 إلى 3
نتعلم الواحد والاثنين والثلاثة.
الدرس 2: الأعداد من 4 إلى 6
نتعلم الأربعة والخمسة والستة.
الوحدة الثانية: الجمع
الدرس 1: جمع ضمن 5
نجمع الأعداد الصغيرة.
AR;

        $chunks = app(CurriculumPdfChunker::class)->chunkFromText($text);

        $this->assertGreaterThanOrEqual(3, count($chunks));
        $this->assertTrue(
            collect($chunks)->contains(fn (array $c): bool => str_contains($c['title'], 'الأعداد من 1 إلى 3')),
        );
        foreach ($chunks as $chunk) {
            $this->assertNotSame('', trim($chunk['content']));
            $this->assertArrayHasKey('title', $chunk);
            $this->assertArrayHasKey('index', $chunk);
        }
    }

    #[Test]
    public function it_falls_back_to_size_based_chunks_when_no_headings(): void
    {
        $text = str_repeat('نص تعليمي عن الجمع والطرح. ', 80);

        $chunks = app(CurriculumPdfChunker::class)->chunkFromText($text, maxLength: 200, overlap: 20);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame(0, $chunks[0]['index']);
        $this->assertSame(1, $chunks[1]['index']);
    }

    #[Test]
    public function it_preserves_arabic_letters_without_reversing(): void
    {
        $text = "الوحدة الأولى\nالدرس 1: حرف الراء\nكلمة رَايَة تبدأ بحرف الراء.";

        $chunks = app(CurriculumPdfChunker::class)->chunkFromText($text);

        $joined = implode("\n", array_column($chunks, 'content'));
        $this->assertStringContainsString('رَايَة', $joined);
        $this->assertStringContainsString('الراء', $joined);
        $this->assertDoesNotMatchRegularExpression('/ةياَر/', $joined);
    }
}
