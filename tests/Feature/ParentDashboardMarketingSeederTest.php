<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Services\Analytics\SubjectAnalyticsService;
use App\Services\Gamification\LeaderboardService;
use Database\Seeders\ParentDashboardMarketingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentDashboardMarketingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_seeder_fills_parent_dashboard_showcase_metrics(): void
    {
        $this->seed(ParentDashboardMarketingSeeder::class);

        $parent = User::query()
            ->where('email', ParentDashboardMarketingSeeder::PARENT_EMAIL)
            ->first();

        $this->assertNotNull($parent);

        $student = Student::query()
            ->where('user_id', $parent->id)
            ->where('name', ParentDashboardMarketingSeeder::SHOWCASE_CHILD_NAME)
            ->first();

        $this->assertNotNull($student);
        $this->assertGreaterThanOrEqual(2000, (int) $student->total_xp);
        $this->assertNotNull($student->tenant_id);
        $this->assertGreaterThanOrEqual(10, (int) ($student->streak?->current_streak ?? 0));

        $analysis = app(SubjectAnalyticsService::class)->analyze($student);
        $this->assertGreaterThanOrEqual(85, $analysis['overall_accuracy_percent']);
        $this->assertGreaterThanOrEqual(4, count($analysis['subject_breakdown']));

        $trend = app(SubjectAnalyticsService::class)->accuracyTrend($student, 7);
        $this->assertTrue($trend['has_data']);

        $completedLessons = (int) $student->quizAttempts()->distinct()->count('learning_material_id');
        $this->assertGreaterThanOrEqual(30, $completedLessons);

        $weeklyXp = (int) (app(LeaderboardService::class)
            ->forGradeLevel((int) $student->grade_level, 'weekly')
            ->firstWhere('id', $student->id)
            ?->weekly_xp ?? 0);
        $this->assertGreaterThanOrEqual(400, $weeklyXp);

        $rank = app(LeaderboardService::class)->rankForStudent($student, 'weekly');
        $this->assertLessThanOrEqual(3, $rank);
    }
}
