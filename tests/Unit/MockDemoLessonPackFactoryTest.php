<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Curriculum\MockDemo\MockDemoLessonPackFactory;
use App\Support\Curriculum\MockDemo\MockDemoTopicCatalog;
use App\Support\Curriculum\PalestinianLessonSchema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MockDemoLessonPackFactoryTest extends TestCase
{
    #[Test]
    public function it_builds_schema_valid_packs_for_each_subject_template(): void
    {
        $factory = new MockDemoLessonPackFactory;
        $catalog = new MockDemoTopicCatalog;

        foreach ([1, 3, 6] as $grade) {
            foreach (['arabic', 'math', 'science'] as $subject) {
                $topics = $catalog->topicsFor($grade, $subject, 1);
                $this->assertNotEmpty($topics, "Expected topics for grade {$grade} {$subject}");

                $pack = $factory->make(
                    grade: $grade,
                    semester: 1,
                    subject: $subject,
                    topic: $topics[0],
                    lessonIndex: 0,
                    orderColumn: 10,
                );

                PalestinianLessonSchema::assertValid($pack);
                $this->assertSame($grade, $pack['grade_level']);
                $this->assertGreaterThanOrEqual(3, count($pack['quiz']['questions']));
                $this->assertLessThanOrEqual(5, count($pack['quiz']['questions']));
                $this->assertArrayHasKey('1', $pack['mascot_hints']['station_prompts']);
                $this->assertStringContainsString('سنبل', (string) $pack['mascot_hints']['station_prompts']['1']);
            }
        }
    }
}
