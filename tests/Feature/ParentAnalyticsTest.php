<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Http\Middleware\EnsureActiveChildContext;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\StudyRecommendation;
use App\Models\User;
use App\Services\Analytics\SubjectAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
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
}
