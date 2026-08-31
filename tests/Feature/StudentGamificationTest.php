<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Badge;
use App\Models\Student;
use App\Models\User;
use App\Services\Gameplay\ActivitySubmissionService;
use App\Services\Gamification\LeaderboardService;
use App\Services\Gamification\StudentGamification;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_student_level_is_calculated_correctly_from_total_xp(): void
    {
        $student = Student::factory()->create(['total_xp' => 250]);

        $this->assertSame(3, StudentGamification::levelForXp($student->total_xp));
        $this->assertSame(50, StudentGamification::xpTowardsNextLevel($student->total_xp));
        $this->assertSame(50, StudentGamification::progressPercent($student->total_xp));
    }

    public function test_completing_activity_triggers_badge_unlocks(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => 50,
                'payload' => $this->samplePayload(),
            ]);

        app(ActivitySubmissionService::class)->submit(
            $activity,
            $student,
            $this->allCorrectAnswers(),
        );

        $student->refresh();

        $this->assertTrue($student->badges()->where('slug', 'first_activity')->exists());
        $this->assertTrue($student->badges()->where('slug', 'perfect_score')->exists());
        $this->assertNotNull($student->badges()->where('slug', 'first_activity')->first()?->pivot?->unlocked_at);
    }

    public function test_leaderboard_ranks_students_within_same_grade_level(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create(['name' => 'Low', 'grade_level' => 3, 'total_xp' => 100]);
        Student::factory()->for($parent)->create(['name' => 'Mid', 'grade_level' => 3, 'total_xp' => 300]);
        Student::factory()->for($parent)->create(['name' => 'Top', 'grade_level' => 3, 'total_xp' => 500]);

        $leaderboard = app(LeaderboardService::class)->forGradeLevel(3);

        $this->assertSame(['Top', 'Mid', 'Low'], $leaderboard->pluck('name')->all());
        $this->assertSame([500, 300, 100], $leaderboard->pluck('total_xp')->all());
    }

    public function test_leaderboard_does_not_leak_students_from_other_grade_levels(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create(['name' => 'Grade Three', 'grade_level' => 3, 'total_xp' => 200]);
        Student::factory()->for($parent)->create(['name' => 'Grade One', 'grade_level' => 1, 'total_xp' => 900]);

        $leaderboard = app(LeaderboardService::class)->forGradeLevel(3);

        $this->assertCount(1, $leaderboard);
        $this->assertSame('Grade Three', $leaderboard->first()->name);
    }

    public function test_parent_can_view_child_progress_and_earned_badges(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'total_xp' => 250,
            'grade_level' => 4,
        ]);
        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'title' => 'نشاط الرياضيات',
                'payload' => $this->samplePayload(),
            ]);

        ActivityAttempt::factory()->for($student)->for($activity)->create([
            'score' => 5,
            'total_questions' => 5,
            'xp_earned' => 50,
            'completed_at' => now(),
        ]);

        $firstActivityBadge = Badge::query()->where('slug', 'first_activity')->firstOrFail();
        $student->badges()->attach($firstActivityBadge->id, ['unlocked_at' => now()]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.progress'));

        $response->assertOk()
            ->assertViewIs('student.progress.index')
            ->assertSee('Level 3')
            ->assertSee('250')
            ->assertSee('البداية المشرقة')
            ->assertSee('نشاط الرياضيات')
            ->assertViewHas('progressPercent', 50)
            ->assertViewHas('recentAttempts');
    }

    /**
     * @return array{questions: list<array<string, mixed>>}
     */
    private function samplePayload(): array
    {
        return [
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'question' => '2 + 2 = ؟',
                    'options' => ['3', '4', '5', '6'],
                    'correct_index' => 1,
                    'explanation' => '2 + 2 = 4',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '5 × 5 = ؟',
                    'options' => ['20', '25', '30', '35'],
                    'correct_index' => 1,
                    'explanation' => '5 × 5 = 25',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '10 ÷ 2 = ؟',
                    'options' => ['4', '5', '6', '7'],
                    'correct_index' => 1,
                    'explanation' => '10 ÷ 2 = 5',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '3 + 3 = ؟',
                    'options' => ['5', '6', '7', '8'],
                    'correct_index' => 1,
                    'explanation' => '3 + 3 = 6',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '9 - 4 = ؟',
                    'options' => ['4', '5', '6', '7'],
                    'correct_index' => 1,
                    'explanation' => '9 - 4 = 5',
                ],
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function allCorrectAnswers(): array
    {
        return [1, 1, 1, 1, 1];
    }
}
