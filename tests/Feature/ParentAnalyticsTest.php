<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Http\Middleware\EnsureActiveChildContext;
use App\Jobs\GenerateStudyRecommendationsJob;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\StudyRecommendation;
use App\Models\User;
use App\Services\AI\AIStudyRecommendationService;
use App\Services\Analytics\SubjectAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use RuntimeException;
use Tests\TestCase;

class ParentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_view_subject_accuracy_and_performance_breakdown(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'grade_level' => 3,
            'school_term' => 1,
        ]);

        $this->seedMaterialAttempt($parent, $student, 'Math', accuracyPercent: 80);
        $this->seedMaterialAttempt($parent, $student, 'Science', accuracyPercent: 40);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.analytics'));

        $response->assertOk()
            ->assertSee('Math')
            ->assertSee('Science')
            ->assertSee('80%')
            ->assertSee('40%')
            ->assertSee('60%');
    }

    public function test_system_identifies_weak_topics_below_60_percent_threshold(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->seedMaterialAttempt($parent, $student, 'Math', accuracyPercent: 80);
        $this->seedMaterialAttempt($parent, $student, 'Science', accuracyPercent: 40);

        $weaknesses = app(SubjectAnalyticsService::class)->identifyWeaknesses($student);

        $this->assertCount(1, $weaknesses);
        $this->assertSame('Science', $weaknesses->first()['subject']);
        $this->assertSame(40, $weaknesses->first()['accuracy_percent']);
    }

    public function test_ai_study_recommendations_are_generated_and_saved(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleRecommendation()),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'grade_level' => 4,
            'school_term' => 2,
        ]);

        $this->seedMaterialAttempt($parent, $student, 'Math', accuracyPercent: 80);
        $this->seedMaterialAttempt($parent, $student, 'Science', accuracyPercent: 40);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('parent.analytics.generate-recommendations'));

        $response->assertRedirect(route('parent.analytics'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('study_recommendations', [
            'student_id' => $student->id,
            'suggested_focus_area' => 'تركيز على مهارات ضرب الأعداد العشرات',
        ]);

        $recommendation = StudyRecommendation::query()->where('student_id', $student->id)->first();
        $this->assertNotNull($recommendation);
        $this->assertSame('Science', $recommendation->weak_topics_json[0]['subject']);
        $this->assertCount(2, $recommendation->actionable_tips_json);
        $this->assertNotNull($recommendation->generated_at);
    }

    public function test_parent_cannot_view_analytics_or_recommendations_for_another_parents_child(): void
    {
        $parentA = User::factory()->create();
        $childA = Student::factory()->for($parentA)->create();

        $parentB = User::factory()->create();
        Student::factory()->for($parentB)->create();

        $this->withoutMiddleware(EnsureActiveChildContext::class);

        $this->actingAs($parentB)
            ->withSession(['active_student_id' => $childA->id])
            ->get(route('parent.analytics'))
            ->assertForbidden();

        $this->actingAs($parentB)
            ->withSession(['active_student_id' => $childA->id])
            ->post(route('parent.analytics.generate-recommendations'))
            ->assertForbidden();

        $this->assertDatabaseCount('study_recommendations', 0);
    }

    public function test_fresh_student_analytics_shows_empty_state_without_errors(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.analytics'));

        $response->assertOk()
            ->assertSee('0%')
            ->assertSee('لا توجد بيانات كافية بعد');

        $analysis = app(SubjectAnalyticsService::class)->analyze($student);

        $this->assertSame(0, $analysis['total_questions_attempted']);
        $this->assertSame(0, $analysis['overall_accuracy_percent']);
        $this->assertSame([], $analysis['subject_breakdown']);
    }

    public function test_weakness_threshold_excludes_exactly_sixty_percent_and_includes_fifty_nine_point_nine(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $service = app(SubjectAnalyticsService::class);

        $this->seedMaterialAttemptWithScore($parent, $student, 'Exact Sixty', score: 3, totalQuestions: 5);
        $this->seedMaterialAttemptWithScore($parent, $student, 'Borderline Weak', score: 599, totalQuestions: 1000);

        $weaknesses = $service->identifyWeaknesses($student);

        $this->assertFalse($weaknesses->contains('subject', 'Exact Sixty'));
        $this->assertTrue($weaknesses->contains('subject', 'Borderline Weak'));
        $this->assertSame(60, $service->analyze($student)['subject_breakdown'][0]['accuracy_percent']);
        $this->assertTrue($service->isWeakAccuracy(599, 1000));
        $this->assertFalse($service->isWeakAccuracy(3, 5));
    }

    public function test_generate_recommendations_handles_ai_service_failures_gracefully(): void
    {
        $this->mock(AIStudyRecommendationService::class, function ($mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new RuntimeException('HTTP 429 Too Many Requests'));
        });

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedMaterialAttempt($parent, $student, 'Science', accuracyPercent: 40);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('parent.analytics.generate-recommendations'));

        $response->assertRedirect(route('parent.analytics'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('status');
        $this->assertDatabaseCount('study_recommendations', 0);
    }

    public function test_generate_recommendations_is_throttled_to_once_every_five_minutes_per_child(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleRecommendation()),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedMaterialAttempt($parent, $student, 'Science', accuracyPercent: 40);

        $session = ['active_student_id' => $student->id];

        $this->actingAs($parent)
            ->withSession($session)
            ->post(route('parent.analytics.generate-recommendations'))
            ->assertRedirect(route('parent.analytics'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('study_recommendations', 1);

        $this->actingAs($parent)
            ->withSession($session)
            ->post(route('parent.analytics.generate-recommendations'))
            ->assertRedirect(route('parent.analytics'))
            ->assertSessionHas('error')
            ->assertSessionMissing('status');

        $this->assertDatabaseCount('study_recommendations', 1);
    }

    public function test_recommendation_job_remains_bound_to_dispatched_child_after_session_switch(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleRecommendation()),
        ]);

        Queue::fake();

        $parent = User::factory()->create();
        $childA = Student::factory()->for($parent)->create(['name' => 'Child A']);
        $childB = Student::factory()->for($parent)->create(['name' => 'Child B']);

        $this->seedMaterialAttempt($parent, $childA, 'Math', accuracyPercent: 40);
        $this->seedMaterialAttempt($parent, $childB, 'Science', accuracyPercent: 80);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $childA->id])
            ->post(route('parent.analytics.generate-recommendations'))
            ->assertRedirect(route('parent.analytics'));

        Queue::assertPushed(GenerateStudyRecommendationsJob::class, function (GenerateStudyRecommendationsJob $job) use ($childA): bool {
            return $job->studentId === $childA->id;
        });

        $pushedJob = null;

        Queue::assertPushed(GenerateStudyRecommendationsJob::class, function (GenerateStudyRecommendationsJob $job) use (&$pushedJob): bool {
            $pushedJob = $job;

            return true;
        });

        $this->assertNotNull($pushedJob);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $childB->id]);

        $pushedJob->handle(app(AIStudyRecommendationService::class));

        $this->assertDatabaseHas('study_recommendations', [
            'student_id' => $childA->id,
        ]);
        $this->assertDatabaseMissing('study_recommendations', [
            'student_id' => $childB->id,
        ]);
    }

    /**
     * @return array{focus_area: string, parent_tips: list<string>}
     */
    private function sampleRecommendation(): array
    {
        return [
            'focus_area' => 'تركيز على مهارات ضرب الأعداد العشرات',
            'parent_tips' => [
                'استخدام الوسائل البصرية في شرح الكسور',
                'مراجعة الاختبار السابق لمدة 10 دقائق يومياً',
            ],
        ];
    }

    private function seedMaterialAttempt(
        User $parent,
        Student $student,
        string $subjectTitle,
        int $accuracyPercent,
    ): void {
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => $subjectTitle,
                'status' => MaterialStatus::Completed,
            ]);

        $activity = Activity::factory()
            ->for($student)
            ->create([
                'parent_material_id' => $material->id,
                'status' => ActivityStatus::Published,
            ]);

        $totalQuestions = 5;
        $score = (int) round($totalQuestions * ($accuracyPercent / 100));

        ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'score' => $score,
                'total_questions' => $totalQuestions,
                'xp_earned' => 10,
                'completed_at' => now(),
            ]);
    }

    private function seedMaterialAttemptWithScore(
        User $parent,
        Student $student,
        string $subjectTitle,
        int $score,
        int $totalQuestions,
    ): void {
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => $subjectTitle,
                'status' => MaterialStatus::Completed,
            ]);

        $activity = Activity::factory()
            ->for($student)
            ->create([
                'parent_material_id' => $material->id,
                'status' => ActivityStatus::Published,
            ]);

        ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'score' => $score,
                'total_questions' => $totalQuestions,
                'xp_earned' => 10,
                'completed_at' => now(),
            ]);
    }
}
