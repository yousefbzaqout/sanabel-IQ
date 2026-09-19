<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Services\Ai\ArabicPronunciationScorer;
use App\Services\Ai\LetterStrokeDirectionAnalyzer;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiVoiceTracingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_pronunciation_scorer_matches_target_phoneme_transcripts(): void
    {
        $scorer = new ArabicPronunciationScorer;

        $match = $scorer->score('رَ', 'را');
        $this->assertSame('match', $match['result']);
        $this->assertGreaterThanOrEqual(70, $match['score']);

        $damma = $scorer->score('رُ', 'رو');
        $this->assertSame('match', $damma['result']);

        $kasra = $scorer->score('رِ', 'ري');
        $this->assertSame('match', $kasra['result']);

        $retry = $scorer->score('رَ', 'باب');
        $this->assertSame('retry', $retry['result']);
        $this->assertLessThan(70, $retry['score']);
    }

    public function test_voice_analyzer_endpoint_handles_payload(): void
    {
        [$parent, $student] = $this->seedStudent();

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['result', 'score', 'feedback'])
            ->assertJsonPath('result', 'match');

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'xyz',
                'transcript' => 'hello',
            ])
            ->assertStatus(422);
    }

    public function test_stroke_direction_analyzer_scores_top_to_bottom_arc(): void
    {
        $analyzer = new LetterStrokeDirectionAnalyzer;

        $goodStroke = [
            ['x' => 70, 'y' => 30],
            ['x' => 90, 'y' => 45],
            ['x' => 105, 'y' => 70],
            ['x' => 100, 'y' => 95],
            ['x' => 70, 'y' => 110],
            ['x' => 45, 'y' => 90],
        ];

        $good = $analyzer->score($goodStroke);
        $this->assertSame('match', $good['result']);
        $this->assertGreaterThanOrEqual(70, $good['score']);
        $this->assertTrue($good['direction_ok']);

        $bottomUp = [
            ['x' => 70, 'y' => 110],
            ['x' => 90, 'y' => 95],
            ['x' => 105, 'y' => 70],
            ['x' => 90, 'y' => 45],
            ['x' => 70, 'y' => 30],
        ];

        $bad = $analyzer->score($bottomUp);
        $this->assertSame('retry', $bad['result']);
        $this->assertFalse($bad['direction_ok']);
    }

    public function test_tracing_score_endpoint_handles_payload(): void
    {
        [$parent, $student] = $this->seedStudent();

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.stroke'), [
                'points' => [
                    ['x' => 70, 'y' => 28],
                    ['x' => 95, 'y' => 50],
                    ['x' => 108, 'y' => 75],
                    ['x' => 90, 'y' => 105],
                    ['x' => 40, 'y' => 85],
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['result', 'score', 'direction_ok', 'feedback'])
            ->assertJsonPath('result', 'match');

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.stroke'), [
                'points' => [['x' => 1, 'y' => 1]],
            ])
            ->assertStatus(422);
    }

    /**
     * @return array{0: User, 1: Student}
     */
    private function seedStudent(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        return [$parent, $student];
    }
}
