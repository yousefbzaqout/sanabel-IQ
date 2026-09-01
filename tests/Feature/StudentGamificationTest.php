<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Events\BadgeUnlockedBroadcastEvent;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Badge;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\StudentStreak;
use App\Models\User;
use App\Services\Gamification\BadgeEvaluatorService;
use App\Services\Gamification\LeaderboardService;
use App\Services\Gamification\StreakTrackerService;
use App\Services\Gamification\StudentGamification;
use App\Services\Gameplay\ActivitySubmissionService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        $this->assertTrue($student->badges()->where('code', 'first_activity')->exists());
        $this->assertTrue($student->badges()->where('code', 'perfect_score')->exists());
        $this->assertNotNull($student->badges()->where('code', 'first_activity')->first()?->pivot?->unlocked_at);
    }

    public function test_first_perfect_submission_unlocks_multiple_badges_without_duplicate_pivot_rows(): void
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

        $this->assertSame(2, $student->badges()->count());
        $this->assertDatabaseCount('student_badges', 2);
        $this->assertTrue($student->badges()->where('code', 'first_activity')->exists());
        $this->assertTrue($student->badges()->where('code', 'perfect_score')->exists());
        $this->assertSame(
            2,
            $student->badges()->whereIn('code', ['first_activity', 'perfect_score'])->count(),
        );
    }

    public function test_xp_level_boundary_cases(): void
    {
        $this->assertSame(1, StudentGamification::levelForXp(0));
        $this->assertSame(0, StudentGamification::xpTowardsNextLevel(0));
        $this->assertSame(0, StudentGamification::progressPercent(0));

        $this->assertSame(1, StudentGamification::levelForXp(99));
        $this->assertSame(99, StudentGamification::xpTowardsNextLevel(99));
        $this->assertSame(99, StudentGamification::progressPercent(99));

        $this->assertSame(2, StudentGamification::levelForXp(100));
        $this->assertSame(0, StudentGamification::xpTowardsNextLevel(100));
        $this->assertSame(0, StudentGamification::progressPercent(100));
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

    public function test_leaderboard_cache_is_flushed_after_xp_update(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create(['name' => 'Leader One', 'grade_level' => 3, 'total_xp' => 500]);
        Student::factory()->for($parent)->create(['name' => 'Leader Two', 'grade_level' => 3, 'total_xp' => 400]);
        Student::factory()->for($parent)->create(['name' => 'Leader Three', 'grade_level' => 3, 'total_xp' => 300]);
        $childC = Student::factory()->for($parent)->create(['name' => 'Child C', 'grade_level' => 3, 'total_xp' => 100]);

        $leaderboardService = app(LeaderboardService::class);

        $cachedLeaderboard = $leaderboardService->forGradeLevel(3);
        $this->assertSame('Leader One', $cachedLeaderboard->first()->name);
        $this->assertSame('Child C', $cachedLeaderboard->last()->name);

        $activity = Activity::factory()
            ->for($childC)
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => 450,
                'payload' => $this->samplePayload(),
            ]);

        app(ActivitySubmissionService::class)->submit(
            $activity,
            $childC,
            $this->allCorrectAnswers(),
        );

        $refreshedLeaderboard = $leaderboardService->forGradeLevel(3);

        $this->assertSame('Child C', $refreshedLeaderboard->first()->name);
        $this->assertSame(550, $refreshedLeaderboard->first()->total_xp);
    }

    public function test_leaderboard_masks_student_names_for_privacy(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'Amina Hassan',
            'grade_level' => 3,
            'total_xp' => 120,
        ]);
        Student::factory()->for($parent)->create([
            'name' => 'Omar Al-Rashid',
            'grade_level' => 3,
            'total_xp' => 80,
        ]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.leaderboard'));

        $response->assertOk()
            ->assertSee('Amina H.')
            ->assertSee('Omar A.');

        $entries = $response->viewData('entries');
        $this->assertSame('Amina H.', $entries->first()['display_name']);
        $this->assertSame('Omar A.', $entries->last()['display_name']);
        $this->assertStringNotContainsString('Hassan', $entries->first()['display_name']);
        $this->assertStringNotContainsString('Al-Rashid', $entries->last()['display_name']);
    }

    public function test_leaderboard_handles_empty_and_single_student_grade_levels(): void
    {
        $parent = User::factory()->create();
        $onlyStudent = Student::factory()->for($parent)->create([
            'name' => 'Solo Learner',
            'grade_level' => 6,
            'total_xp' => 15,
        ]);

        $this->assertCount(0, app(LeaderboardService::class)->forGradeLevel(2));

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $onlyStudent->id])
            ->get(route('student.leaderboard'))
            ->assertOk()
            ->assertSee('Solo L.')
            ->assertSee('#1');
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

        $firstActivityBadge = Badge::query()->where('code', 'first_activity')->firstOrFail();
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

    public function test_completing_first_quiz_unlocks_first_step_badge(): void
    {
        Event::fake([BadgeUnlockedBroadcastEvent::class]);

        Badge::query()->updateOrCreate(['code' => 'first_quiz'], [
            'name_ar' => 'أول خطوة',
            'description_ar' => 'إكمال أول اختبار',
            'icon' => 'footprints',
            'criteria_type' => 'quiz_count',
            'criteria_value' => 1,
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 50]);
        $question = Question::factory()->for($material)->mcq()->create(['order_column' => 0]);
        $correct = QuestionOption::factory()->for($question)->correct()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->create(['order_column' => 1]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $material), [
                'answers' => [[
                    'question_id' => $question->id,
                    'selected_option_id' => $correct->id,
                ]],
            ])
            ->assertOk();

        $this->assertTrue($student->fresh()->badges()->where('code', 'first_quiz')->exists());
        $this->assertDatabaseHas('student_badges', [
            'student_id' => $student->id,
        ]);

        Event::assertDispatched(BadgeUnlockedBroadcastEvent::class, function (BadgeUnlockedBroadcastEvent $event) use ($parent, $student): bool {
            if ($event->broadcastWith()['badge_code'] !== 'first_quiz') {
                return false;
            }

            $channels = $event->broadcastOn();
            $channelNames = array_map(fn ($channel) => $channel->name, $channels);

            $this->assertContains('private-parent.'.$parent->id, $channelNames);
            $this->assertContains('private-student.'.$student->id, $channelNames);
            $this->assertSame('أول خطوة', $event->broadcastWith()['badge_name_ar']);

            return true;
        });
    }

    public function test_daily_activity_maintains_and_increments_streak(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        $student = Student::factory()->create();

        app(StreakTrackerService::class)->recordActivity($student);
        $this->assertDatabaseHas('student_streaks', [
            'student_id' => $student->id,
            'current_streak' => 1,
            'max_streak' => 1,
            'last_activity_date' => '2026-01-01',
        ]);

        Carbon::setTestNow('2026-01-02 10:00:00');

        app(StreakTrackerService::class)->recordActivity($student->fresh());

        $this->assertDatabaseHas('student_streaks', [
            'student_id' => $student->id,
            'current_streak' => 2,
            'max_streak' => 2,
            'last_activity_date' => '2026-01-02',
        ]);

        Carbon::setTestNow();
    }

    public function test_missing_a_day_resets_current_streak_to_one(): void
    {
        $student = Student::factory()->create();

        StudentStreak::factory()->for($student)->create([
            'current_streak' => 5,
            'max_streak' => 5,
            'last_activity_date' => now()->subDays(2)->toDateString(),
        ]);

        app(StreakTrackerService::class)->recordActivity($student->fresh());

        $streak = $student->fresh()?->streak;
        $this->assertNotNull($streak);
        $this->assertSame(1, $streak->current_streak);
        $this->assertSame(5, $streak->max_streak);
        $this->assertSame(now()->toDateString(), $streak->last_activity_date?->toDateString());
    }

    public function test_leaderboard_ranks_students_by_grade_level_and_xp(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create(['name' => 'Low', 'grade_level' => 3, 'total_xp' => 100]);
        Student::factory()->for($parent)->create(['name' => 'Mid', 'grade_level' => 3, 'total_xp' => 300]);
        $activeStudent = Student::factory()->for($parent)->create(['name' => 'Top', 'grade_level' => 3, 'total_xp' => 500]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $activeStudent->id])
            ->getJson(route('student.leaderboard', ['grade' => 3]))
            ->assertOk()
            ->assertJsonStructure([
                'grade_level',
                'period',
                'active_rank',
                'entries' => [
                    '*' => ['rank', 'display_name', 'total_xp', 'level', 'current_streak'],
                ],
            ])
            ->assertJsonPath('grade_level', 3)
            ->assertJsonPath('entries.0.rank', 1)
            ->assertJsonPath('entries.0.total_xp', 500)
            ->assertJsonPath('entries.1.rank', 2)
            ->assertJsonPath('entries.1.total_xp', 300)
            ->assertJsonPath('entries.2.rank', 3)
            ->assertJsonPath('entries.2.total_xp', 100);
    }

    public function test_same_day_multiple_quizzes_increment_streak_only_once(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);
        Carbon::setTestNow(Carbon::parse('2026-01-05 09:00:00', 'Asia/Riyadh'));

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);

        for ($index = 0; $index < 3; $index++) {
            $material = LearningMaterial::factory()->published()->create([
                'title' => 'Quiz '.$index,
                'xp_reward' => 10,
            ]);
            $question = Question::factory()->for($material)->mcq()->create(['order_column' => 0]);
            $correct = QuestionOption::factory()->for($question)->correct()->create(['order_column' => 0]);
            QuestionOption::factory()->for($question)->create(['order_column' => 1]);

            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->postJson(route('student.materials.quiz.submit', $material), [
                    'answers' => [[
                        'question_id' => $question->id,
                        'selected_option_id' => $correct->id,
                    ]],
                ])
                ->assertOk();
        }

        $streak = $student->fresh()?->streak;
        $this->assertNotNull($streak);
        $this->assertSame(1, $streak->current_streak);
        $this->assertSame(1, $streak->max_streak);
        $this->assertSame('2026-01-05', $streak->last_activity_date?->toDateString());

        Carbon::setTestNow();
    }

    public function test_timezone_gap_resets_streak_while_preserving_max_streak(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);

        $student = Student::factory()->create();

        Carbon::setTestNow(Carbon::parse('2026-01-04 20:00:00', 'Asia/Riyadh'));
        app(StreakTrackerService::class)->recordActivity($student);

        Carbon::setTestNow(Carbon::parse('2026-01-05 23:50:00', 'Asia/Riyadh'));
        app(StreakTrackerService::class)->recordActivity($student->fresh());

        $this->assertSame(2, $student->fresh()?->streak?->current_streak);
        $this->assertSame(2, $student->fresh()?->streak?->max_streak);

        Carbon::setTestNow(Carbon::parse('2026-01-07 08:00:00', 'Asia/Riyadh'));
        app(StreakTrackerService::class)->recordActivity($student->fresh());

        $streak = $student->fresh()?->streak;
        $this->assertNotNull($streak);
        $this->assertSame(1, $streak->current_streak);
        $this->assertSame(2, $streak->max_streak);
        $this->assertSame('2026-01-07', $streak->last_activity_date?->toDateString());

        Carbon::setTestNow();
    }

    public function test_repeated_badge_evaluation_is_idempotent_without_duplicate_events(): void
    {
        $student = Student::factory()->create(['total_xp' => 0]);

        $badge = Badge::query()->updateOrCreate(['code' => 'first_quiz'], [
            'name_ar' => 'أول خطوة',
            'description_ar' => 'إكمال أول اختبار',
            'icon' => 'footprints',
            'criteria_type' => 'quiz_count',
            'criteria_value' => 1,
        ]);

        $student->badges()->attach($badge->id, ['unlocked_at' => now()]);

        Event::fake([BadgeUnlockedBroadcastEvent::class]);

        $evaluator = app(BadgeEvaluatorService::class);

        for ($index = 0; $index < 3; $index++) {
            $unlocked = $evaluator->evaluate($student->fresh());
            $this->assertCount(0, $unlocked);
        }

        $this->assertDatabaseCount('student_badges', 1);
        Event::assertNotDispatched(BadgeUnlockedBroadcastEvent::class);
    }

    public function test_badge_unlock_broadcast_is_isolated_to_student_parent_channels(): void
    {
        Event::fake([BadgeUnlockedBroadcastEvent::class]);

        Badge::query()->updateOrCreate(['code' => 'first_quiz'], [
            'name_ar' => 'أول خطوة',
            'description_ar' => 'إكمال أول اختبار',
            'icon' => 'footprints',
            'criteria_type' => 'quiz_count',
            'criteria_value' => 1,
        ]);

        $parent = User::factory()->create();
        $otherParent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 10]);
        $question = Question::factory()->for($material)->mcq()->create(['order_column' => 0]);
        $correct = QuestionOption::factory()->for($question)->correct()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->create(['order_column' => 1]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $material), [
                'answers' => [[
                    'question_id' => $question->id,
                    'selected_option_id' => $correct->id,
                ]],
            ])
            ->assertOk();

        Event::assertDispatched(BadgeUnlockedBroadcastEvent::class, function (BadgeUnlockedBroadcastEvent $event) use ($parent, $otherParent, $student): bool {
            $channelNames = array_map(fn ($channel) => $channel->name, $event->broadcastOn());

            $this->assertContains('private-parent.'.$parent->id, $channelNames);
            $this->assertContains('private-student.'.$student->id, $channelNames);
            $this->assertNotContains('private-parent.'.$otherParent->id, $channelNames);

            return true;
        });
    }

    public function test_leaderboard_tie_breaks_by_student_id_and_avoids_streak_n_plus_one_queries(): void
    {
        $parent = User::factory()->create();
        $students = collect();

        for ($index = 0; $index < 5; $index++) {
            $students->push(Student::factory()->for($parent)->create([
                'name' => 'Tie '.$index,
                'grade_level' => 4,
                'total_xp' => 200,
            ]));
        }

        $activeStudent = $students->first();
        $expectedOrder = $students->sortBy('id')->values();

        app(LeaderboardService::class)->flushGradeLevel(4);
        Cache::flush();

        $leaderboard = app(LeaderboardService::class)->forGradeLevel(4);
        $this->assertSame(
            $expectedOrder->pluck('id')->all(),
            $leaderboard->pluck('id')->all(),
        );

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $activeStudent->id])
            ->getJson(route('student.leaderboard', ['grade' => 4]))
            ->assertOk()
            ->assertJsonCount(5, 'entries')
            ->assertJsonPath('entries.0.rank', 1)
            ->assertJsonPath('entries.4.rank', 5);

        $streakQueries = collect(DB::getQueryLog())
            ->filter(static fn (array $query): bool => str_contains(strtolower($query['query']), 'student_streaks'))
            ->values();

        $this->assertLessThanOrEqual(1, $streakQueries->count());
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
