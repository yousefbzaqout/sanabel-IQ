<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Curriculum\PalestinianLessonSchema;
use App\Support\Curriculum\Pipeline\CurriculumPdfChunker;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

class GenerateLessonFromPdfCommandTest extends TestCase
{
    #[Test]
    public function it_generates_validated_json_lesson_pack_from_pdf_text_via_prism(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleMathLessonPack()),
        ]);

        $pdfRelative = 'palestine/grade_1/semester_1/math.pdf';
        $absolute = storage_path('curriculum/'.$pdfRelative);
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, '%PDF-1.4'."\n".'الوحدة الأولى'."\n".'الدرس 1: الأعداد ١ إلى ٣'."\n".str_repeat('نعد التفاح. ', 40));

        $this->mock(CurriculumPdfChunker::class, function ($mock): void {
            $mock->shouldReceive('chunkFromPdf')
                ->once()
                ->andReturn([
                    [
                        'index' => 0,
                        'title' => 'الدرس 1: الأعداد ١ إلى ٣',
                        'content' => 'نتعلم الأعداد واحد واثنان وثلاثة مع العدّ البصري.',
                    ],
                ]);
        });

        $this->artisan('curriculum:generate-from-pdf', [
            'filePath' => $pdfRelative,
            'grade' => 1,
            'subject' => 'math',
            '--semester' => 1,
            '--limit' => 1,
        ])->assertSuccessful();

        $out = database_path('data/palestinian_curriculum/grade1/math/lessons/ai-generated-numbers-1.json');
        $this->assertFileExists($out);

        try {
            /** @var array<string, mixed> $pack */
            $pack = json_decode((string) File::get($out), true, 512, JSON_THROW_ON_ERROR);
            PalestinianLessonSchema::assertValid($pack);
            $this->assertSame('MATH', $pack['subject_code']);
            $this->assertSame('number', $pack['content_kind']);
        } finally {
            File::delete($out);
        }
    }

    #[Test]
    public function pipeline_report_lists_downloaded_pdfs_and_lesson_packs(): void
    {
        File::ensureDirectoryExists(storage_path('curriculum/palestine/grade_1/semester_1'));
        File::put(storage_path('curriculum/palestine/grade_1/semester_1/science.pdf'), '%PDF-1.4');

        $this->artisan('curriculum:pipeline-report')
            ->assertSuccessful()
            ->expectsOutputToContain('Downloaded textbooks')
            ->expectsOutputToContain('science.pdf')
            ->expectsOutputToContain('Lesson JSON packs');
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleMathLessonPack(): array
    {
        return [
            'schema_version' => '1.0.0',
            'grade_level' => 1,
            'semester' => 1,
            'subject_code' => 'MATH',
            'content_kind' => 'number',
            'lesson_key' => 'ar-g1-math-ai-numbers-1',
            'audio_slug' => 'ai-numbers-1',
            'key' => 'ai-generated-numbers-1',
            'title' => 'الأعداد ١ إلى ٣',
            'subtitle' => 'مولَّد آلياً من الكتاب المدرسي',
            'material_title' => 'AI: الأعداد ١ إلى ٣',
            'digit' => '٣',
            'digits' => ['١', '٢', '٣'],
            'order_column' => 100,
            'xp_reward' => 45,
            'description' => 'درس مولّد من PDF',
            'phonemes' => [
                'voice_targets' => ['١', '٢', '٣'],
                'tabs' => [
                    [
                        'glyph' => '١',
                        'label' => 'واحد',
                        'audio_script' => 'واحد',
                        'cards' => [
                            ['word' => '١', 'emoji' => '🍎', 'audio' => 'واحد', 'highlight' => '١'],
                        ],
                    ],
                ],
            ],
            'stroke' => [
                'label' => 'تتبّع ٣',
                'path' => 'M 48 34 C 78 28 108 38 108 58',
                'direction' => 'digit_stroke',
                'complete_audio' => 'أحسنت!',
            ],
            'quiz' => [
                'questions' => [
                    [
                        'type' => 'mcq',
                        'prompt' => 'كم تفاحة؟',
                        'visual_items' => ['emoji' => '🍎', 'count' => 3],
                        'mascot_hint' => 'تلميح سنبل: عدّ ببطء.',
                        'explanation' => 'ثلاث تفاحات',
                        'options' => [
                            ['text' => '٣', 'correct' => true],
                            ['text' => '٢', 'correct' => false],
                        ],
                    ],
                ],
            ],
            'mascot_hints' => [
                'station_prompts' => [
                    '1' => 'مرحباً! أنا سنبل.',
                    '2' => 'عدّ معي!',
                    '3' => 'طابق الأشكال.',
                    '4' => 'ارسم الرقم.',
                    '5' => 'اكتشف!',
                    '6' => 'راجع ثم اختبر.',
                ],
            ],
        ];
    }
}
