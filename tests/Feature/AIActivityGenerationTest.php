<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Jobs\GenerateActivityFromMaterialJob;
use App\Models\Activity;
use App\Models\MaterialChunk;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

class AIActivityGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_trigger_activity_generation_from_completed_material(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleStructuredActivity()),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'grade_level' => 4,
            'school_term' => 1,
        ]);
        $material = $this->createCompletedMaterial($parent, $student);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('materials.generate-activity', $material));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('activities', [
            'student_id' => $student->id,
            'parent_material_id' => $material->id,
            'status' => ActivityStatus::Published->value,
            'xp_reward' => 50,
        ]);

        $activity = Activity::query()->first();
        $this->assertNotNull($activity);
        $this->assertNotEmpty($activity->payload);
        $this->assertSame('اختبار محوسب: درس مساحة الأشكال الهندسية', $activity->title);
    }

    public function test_cannot_generate_activity_from_pending_or_failed_material(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleStructuredActivity()),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        foreach ([MaterialStatus::Pending, MaterialStatus::Failed] as $status) {
            $material = ParentMaterial::factory()
                ->for($parent)
                ->for($student)
                ->create(['status' => $status]);

            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->post(route('materials.generate-activity', $material))
                ->assertUnprocessable();

            $this->assertDatabaseCount('activities', 0);
        }
    }

    public function test_generated_activity_payload_conforms_to_expected_json_schema(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleStructuredActivity()),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = $this->createCompletedMaterial($parent, $student);

        GenerateActivityFromMaterialJob::dispatchSync($material);

        $activity = Activity::query()->firstOrFail();
        $payload = $activity->payload;

        $this->assertIsArray($payload['questions'] ?? null);
        $this->assertCount(5, $payload['questions']);

        foreach ($payload['questions'] as $question) {
            $this->assertArrayHasKey('question', $question);
            $this->assertArrayHasKey('options', $question);
            $this->assertArrayHasKey('correct_index', $question);
            $this->assertArrayHasKey('explanation', $question);
            $this->assertIsArray($question['options']);
            $this->assertNotSame('', trim((string) $question['question']));
        }
    }

    public function test_parent_cannot_generate_activity_for_another_parents_material(): void
    {
        $parentA = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();
        $material = $this->createCompletedMaterial($parentA, $studentA);

        $parentB = User::factory()->create();
        $studentB = Student::factory()->for($parentB)->create();

        $this->actingAs($parentB)
            ->withSession(['active_student_id' => $studentB->id])
            ->post(route('materials.generate-activity', $material))
            ->assertForbidden();

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_generate_activity_dispatches_job_when_queue_is_faked(): void
    {
        Queue::fake();

        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleStructuredActivity()),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = $this->createCompletedMaterial($parent, $student);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('materials.generate-activity', $material))
            ->assertRedirect(route('dashboard'));

        Queue::assertPushed(
            GenerateActivityFromMaterialJob::class,
            fn (GenerateActivityFromMaterialJob $job): bool => $job->parentMaterial->is($material),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleStructuredActivity(): array
    {
        $questions = [];

        for ($index = 0; $index < 5; $index++) {
            $questions[] = [
                'type' => 'multiple_choice',
                'question' => "ما هي مساحة المربع الذي طول ضلعه {$index} سم؟",
                'options' => ['8 سم²', '16 سم²', '12 سم²', '4 سم²'],
                'correct_index' => 1,
                'explanation' => 'مساحة المربع = الضلع × نفسه',
            ];
        }

        return [
            'title' => 'اختبار محوسب: درس مساحة الأشكال الهندسية',
            'questions' => $questions,
            'total_xp' => 50,
        ];
    }

    private function createCompletedMaterial(User $parent, Student $student): ParentMaterial
    {
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create(['status' => MaterialStatus::Completed]);

        MaterialChunk::factory()->for($material)->create([
            'chunk_index' => 0,
            'content' => 'حساب مساحة المثلث والمربع في الرياضيات للصف الرابع.',
        ]);

        return $material;
    }
}
