<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\User;
use App\Services\Gamification\LeaderboardService;
use App\Services\Observability\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_200_and_ok_status_when_all_services_are_up(): void
    {
        $this->partialMock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkRedis')->andReturn('up');
        });

        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'services' => [
                    'database' => 'up',
                    'redis' => 'up',
                    'storage' => 'up',
                ],
            ]);
    }

    public function test_health_check_returns_503_when_database_or_redis_connection_fails(): void
    {
        $this->partialMock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDatabase')->andReturn('down');
            $mock->shouldReceive('checkRedis')->andReturn('up');
            $mock->shouldReceive('checkStorage')->andReturn('up');
        });

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'degraded',
                'services' => [
                    'database' => 'down',
                    'redis' => 'up',
                    'storage' => 'up',
                ],
            ]);
    }

    public function test_leaderboard_cache_stampede_protection_uses_mutex_on_expiry(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->count(5)->create([
            'grade_level' => 3,
            'total_xp' => 250,
        ]);

        Cache::spy();
        Cache::flush();

        app(LeaderboardService::class)->forGradeLevel(3);

        Cache::shouldHaveReceived('lock')
            ->once()
            ->with('leaderboard.lock.grade.3.alltime', 10);
    }

    public function test_leaderboard_cache_stampede_rebuild_runs_once_for_many_requests(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->count(8)->create([
            'grade_level' => 4,
            'total_xp' => 180,
        ]);

        $service = app(LeaderboardService::class);
        Cache::flush();
        $service->forGradeLevel(4);

        Cache::forget('leaderboard.grade.4.alltime');

        $rebuildQueries = 0;
        DB::listen(function (object $query) use (&$rebuildQueries): void {
            if (
                str_contains(strtolower($query->sql), 'from "students"')
                && str_contains(strtolower($query->sql), 'order by "total_xp" desc')
            ) {
                $rebuildQueries++;
            }
        });

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $service->forGradeLevel(4);
        }

        $this->assertSame(1, $rebuildQueries);
    }

    public function test_leaderboard_endpoint_falls_back_to_database_when_redis_cache_is_unavailable(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'grade_level' => 3,
            'total_xp' => 420,
        ]);

        Cache::partialMock(function (MockInterface $mock): void {
            $mock->shouldReceive('get')->andThrow(new \RuntimeException('Redis connection lost'));
            $mock->shouldReceive('lock')->andThrow(new \RuntimeException('Redis connection lost'));
        });

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('student.leaderboard', ['grade' => 3]))
            ->assertOk()
            ->assertJsonPath('entries.0.total_xp', 420);
    }

    public function test_quiz_submission_degrades_gracefully_when_redis_cache_is_unavailable(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0, 'grade_level' => 3]);
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 90]);

        $question = Question::factory()->for($material)->mcq()->create([
            'prompt' => '2 + 2 = ?',
            'points' => 10,
            'order_column' => 0,
        ]);

        $correct = QuestionOption::factory()->for($question)->correct()->create([
            'option_text' => '4',
            'order_column' => 0,
        ]);

        QuestionOption::factory()->for($question)->create([
            'option_text' => '5',
            'is_correct' => false,
            'order_column' => 1,
        ]);

        Cache::partialMock(function (MockInterface $mock): void {
            $mock->shouldReceive('forget')->andThrow(new \RuntimeException('Redis connection lost'));
            $mock->shouldReceive('get')->andReturn(null);
            $mock->shouldReceive('put')->andReturnTrue();
            $mock->shouldReceive('lock')->andReturnUsing(
                fn (string $name, int $seconds = 0) => Cache::driver()->lock($name, $seconds),
            );
        });

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $material), [
                'answers' => [
                    ['question_id' => $question->id, 'selected_option_id' => $correct->id],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('xp_earned', 90)
            ->assertJsonPath('total_xp', 90);
    }
}
