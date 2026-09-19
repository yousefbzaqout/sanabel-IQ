<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\PalestinianLessonGeneratorService;
use PHPUnit\Framework\Attributes\Test;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

class PalestinianLessonGeneratorServiceTest extends TestCase
{
    #[Test]
    public function it_requests_a_generous_max_tokens_budget_for_openrouter_structured_output(): void
    {
        $fake = Prism::fake([
            StructuredResponseFake::make()->withStructured($this->validMathPack()),
        ]);

        app(PalestinianLessonGeneratorService::class)->generateFromChunk(
            ['index' => 0, 'title' => 'مقطع 1', 'content' => 'نتعلم العدد واحد.'],
            1,
            'math',
            1,
        );

        $fake->assertRequest(function (array $requests): void {
            $this->assertNotEmpty($requests);
            $request = $requests[0];
            $this->assertInstanceOf(StructuredRequest::class, $request);
            $this->assertSame(8192, $request->maxTokens());
        });
    }

    #[Test]
    public function it_fills_missing_lesson_identity_fields_from_chunk_metadata(): void
    {
        $incomplete = $this->validMathPack();
        unset($incomplete['lesson_key'], $incomplete['key'], $incomplete['audio_slug'], $incomplete['material_title']);

        Prism::fake([
            StructuredResponseFake::make()->withStructured($incomplete),
        ]);

        $pack = app(PalestinianLessonGeneratorService::class)->generateFromChunk(
            ['index' => 2, 'title' => 'الدرس 1: الأعداد ١ إلى ٣', 'content' => 'نتعلم الأعداد.'],
            1,
            'math',
            1,
        );

        $this->assertSame('ar-g1-math-ai-chunk-2', $pack['lesson_key']);
        $this->assertSame('ai-chunk-2', $pack['key']);
        $this->assertSame('ai-chunk-2', $pack['audio_slug']);
        $this->assertSame('AI: العدد ١', $pack['material_title']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validMathPack(): array
    {
        return [
            'schema_version' => '1.0.0',
            'grade_level' => 1,
            'semester' => 1,
            'subject_code' => 'MATH',
            'content_kind' => 'number',
            'lesson_key' => 'ar-g1-math-ai-one',
            'audio_slug' => 'ai-one',
            'key' => 'ai-one',
            'title' => 'العدد ١',
            'subtitle' => 'اختبار',
            'material_title' => 'AI: العدد ١',
            'digit' => '١',
            'digits' => ['١'],
            'order_column' => 1,
            'xp_reward' => 40,
            'description' => 'وصف',
            'phonemes' => [
                'voice_targets' => ['١'],
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
                'label' => 'تتبّع',
                'path' => 'M 10 10 L 20 20',
                'direction' => 'digit_stroke',
                'complete_audio' => 'أحسنت',
            ],
            'quiz' => [
                'questions' => [
                    [
                        'type' => 'mcq',
                        'prompt' => 'كم؟',
                        'mascot_hint' => 'عدّ',
                        'explanation' => 'واحد',
                        'options' => [
                            ['text' => '١', 'correct' => 1],
                            ['text' => '٢', 'correct' => 0],
                        ],
                    ],
                ],
            ],
            'mascot_hints' => [
                'station_prompts' => [
                    '1' => 'أ', '2' => 'ب', '3' => 'ج', '4' => 'د', '5' => 'ه', '6' => 'و',
                ],
            ],
        ];
    }
}
