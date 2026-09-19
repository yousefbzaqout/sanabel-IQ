<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_voice_eval_allows_ten_requests_then_returns_429(): void
    {
        [$parent, $student] = $this->seedStudent();
        RateLimiter::clear('ai-voice:'.$student->id);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->postJson(route('student.ai.pronunciation'), [
                    'target' => 'رَ',
                    'transcript' => 'رَ',
                ])
                ->assertOk();
        }

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
            ])
            ->assertStatus(429)
            ->assertJsonPath('error', 'too_many_requests')
            ->assertJsonStructure(['message', 'error']);
    }

    public function test_trace_eval_allows_fifteen_requests_then_returns_429(): void
    {
        [$parent, $student] = $this->seedStudent();
        RateLimiter::clear('ai-trace:'.$student->id);

        $points = [
            ['x' => 70, 'y' => 30],
            ['x' => 90, 'y' => 45],
            ['x' => 105, 'y' => 70],
            ['x' => 100, 'y' => 95],
            ['x' => 70, 'y' => 110],
            ['x' => 45, 'y' => 90],
        ];

        for ($i = 0; $i < 15; $i++) {
            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->postJson(route('student.ai.stroke'), [
                    'points' => $points,
                ])
                ->assertOk();
        }

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.stroke'), [
                'points' => $points,
            ])
            ->assertStatus(429)
            ->assertJsonPath('error', 'too_many_requests');
    }

    public function test_micro_hint_allows_twenty_requests_then_returns_429(): void
    {
        [$parent, $student] = $this->seedStudent();
        RateLimiter::clear('ai-hint:'.$student->id);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->postJson(route('student.ai.micro-hint'), [
                    'concept_key' => 'diacritic_confusion',
                    'lesson_key' => 'ar-g1-letter-raa',
                    'station' => 1,
                    'context' => ['expected' => 'رَ', 'actual' => 'رُ'],
                ])
                ->assertOk()
                ->assertJsonStructure(['show_micro_hint', 'error_count', 'hint_message']);
        }

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.micro-hint'), [
                'concept_key' => 'diacritic_confusion',
                'lesson_key' => 'ar-g1-letter-raa',
                'station' => 1,
            ])
            ->assertStatus(429)
            ->assertJsonPath('error', 'too_many_requests')
            ->assertJsonFragment(['message' => 'لقد تجاوزت الحد المسموح لتلميحات سنبل. حاول بعد دقيقة.']);
    }

    public function test_rate_limits_are_isolated_per_student(): void
    {
        $parent = User::factory()->create();
        $studentA = Student::factory()->for($parent)->create();
        $studentB = Student::factory()->for($parent)->create();

        RateLimiter::clear('ai-voice:'.$studentA->id);
        RateLimiter::clear('ai-voice:'.$studentB->id);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($parent)
                ->withSession(['active_student_id' => $studentA->id])
                ->postJson(route('student.ai.pronunciation'), [
                    'target' => 'رَ',
                    'transcript' => 'رَ',
                ])
                ->assertOk();
        }

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $studentA->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
            ])
            ->assertStatus(429);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $studentB->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
            ])
            ->assertOk();
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
