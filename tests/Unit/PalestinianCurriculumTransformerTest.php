<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Curriculum\PalestinianCurriculumTransformer;
use App\Support\Curriculum\PalestinianLessonSchema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PalestinianCurriculumTransformerTest extends TestCase
{
    #[Test]
    public function it_validates_required_lesson_schema_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('material_title');

        PalestinianLessonSchema::assertValid([
            'schema_version' => '1.0.0',
            'lesson_key' => 'ar-g1-letter-raa',
            'title' => 'حرف الراء',
            'letter' => 'ر',
        ]);
    }

    #[Test]
    public function it_transforms_letter_pack_into_importer_definition_with_voice_stroke_quiz_and_hints(): void
    {
        $pack = $this->sampleLetterPack();

        PalestinianLessonSchema::assertValid($pack);

        $definition = app(PalestinianCurriculumTransformer::class)->toInteractiveDefinition($pack);

        $this->assertSame('ar-g1-letter-raa', $definition['lesson_key']);
        $this->assertSame('AR', $definition['subject_code']);
        $this->assertSame(['رَ', 'رُ', 'رِ'], array_column($definition['variant_tabs'], 'glyph'));
        $this->assertNotEmpty($definition['tracing']['path']);
        $this->assertSame('top_to_bottom_arc', $definition['tracing']['direction']);
        $this->assertStringContainsString('سنبل', $definition['station_copy'][1]['sonbol_prompt']);
        $this->assertCount(3, $definition['quiz']['questions']);
        $this->assertContains($definition['quiz']['questions'][0]['type'], ['mcq', 'true_false']);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleLetterPack(): array
    {
        $path = database_path('data/palestinian_curriculum/grade1/arabic/lessons/letter-raa.json');

        if (! is_file($path)) {
            $this->markTestSkipped('Prototype letter JSON not generated yet.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
