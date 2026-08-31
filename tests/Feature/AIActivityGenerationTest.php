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
use App\Services\AI\ActivityGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
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

        GenerateActivityFromMaterialJob::dispatchSync($material, $student->id);

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
            fn (GenerateActivityFromMaterialJob $job): bool => $job->parentMaterial->is($material)
                && $job->studentId === $student->id,
        );
    }

    public function test_malformed_llm_payload_is_rejected_without_persisting_activity(): void
    {
        Log::spy();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = $this->createCompletedMaterial($parent, $student);

        $malformedPayloads = [
            [
                'title' => 'اختبار ناقص',
                'questions' => [$this->validQuestion()],
            ],
            [
                'title' => 'اختبار فهرس خاطئ',
                'questions' => [
                    array_merge($this->validQuestion(), ['correct_index' => 99]),
                ],
                'total_xp' => 50,
            ],
            [
                'title' => 'اختبار بدون خيارات',
                'questions' => [
                    array_merge($this->validQuestion(), ['options' => []]),
                ],
                'total_xp' => 50,
            ],
            [
                'title' => 'اختبار نوع غير صالح',
                'questions' => [
                    array_merge($this->validQuestion(), ['type' => 'essay']),
                ],
                'total_xp' => 50,
            ],
        ];

        foreach ($malformedPayloads as $payload) {
            Prism::fake([
                StructuredResponseFake::make()->withStructured($payload),
            ]);

            try {
                GenerateActivityFromMaterialJob::dispatchSync($material, $student->id);
                $this->fail('Expected malformed payload to throw an exception.');
            } catch (\Throwable) {
                // Expected validation failure.
            }

            $this->assertDatabaseCount('activities', 0);
        }

        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_multiple_activity_generations_create_distinct_published_records(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleStructuredActivity()),
            StructuredResponseFake::make()->withStructured(array_merge(
                $this->sampleStructuredActivity(),
                ['title' => 'اختبار محوسب: جولة ثانية'],
            )),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = $this->createCompletedMaterial($parent, $student);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('materials.generate-activity', $material))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('materials.generate-activity', $material))
            ->assertRedirect(route('dashboard'));

        $activities = Activity::query()
            ->where('parent_material_id', $material->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $activities);
        $this->assertNotSame($activities->first()->id, $activities->last()->id);
        $this->assertSame(ActivityStatus::Published, $activities->first()->status);
        $this->assertSame(ActivityStatus::Published, $activities->last()->status);
        $this->assertNotSame($activities->first()->title, $activities->last()->title);
    }

    public function test_activity_retains_dispatch_time_student_id_after_active_child_context_switch(): void
    {
        Queue::fake();

        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->sampleStructuredActivity()),
        ]);

        $parent = User::factory()->create();
        $childA = Student::factory()->for($parent)->create(['name' => 'Child A']);
        $childB = Student::factory()->for($parent)->create(['name' => 'Child B']);
        $material = $this->createCompletedMaterial($parent, $childA);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $childA->id])
            ->post(route('materials.generate-activity', $material))
            ->assertRedirect(route('dashboard'));

        /** @var GenerateActivityFromMaterialJob|null $queuedJob */
        $queuedJob = null;

        Queue::assertPushed(
            GenerateActivityFromMaterialJob::class,
            function (GenerateActivityFromMaterialJob $job) use (&$queuedJob, $material, $childA): bool {
                $queuedJob = $job;

                return $job->parentMaterial->is($material) && $job->studentId === $childA->id;
            },
        );

        $this->assertNotNull($queuedJob);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $childB->id])
            ->get(route('dashboard'))
            ->assertOk();

        $queuedJob->handle(app(ActivityGeneratorService::class));

        $this->assertDatabaseHas('activities', [
            'student_id' => $childA->id,
            'parent_material_id' => $material->id,
            'status' => ActivityStatus::Published->value,
        ]);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_draft_activity_cannot_be_accessed_by_parent(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = $this->createCompletedMaterial($parent, $student);

        $draftActivity = Activity::factory()
            ->for($student)
            ->for($material)
            ->create(['status' => ActivityStatus::Draft]);

        $publishedActivity = Activity::factory()
            ->for($student)
            ->for($material)
            ->create(['status' => ActivityStatus::Published]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('activities.show', $draftActivity))
            ->assertForbidden();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('activities.show', $publishedActivity))
            ->assertOk()
            ->assertJsonPath('id', $publishedActivity->id);
    }

    public function test_cross_parent_generation_is_forbidden_before_job_dispatch(): void
    {
        Queue::fake();

        $parentA = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();
        $material = $this->createCompletedMaterial($parentA, $studentA);

        $parentB = User::factory()->create();
        $studentB = Student::factory()->for($parentB)->create();

        $this->actingAs($parentB)
            ->withSession(['active_student_id' => $studentB->id])
            ->post(route('materials.generate-activity', $material))
            ->assertForbidden();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('activities', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function validQuestion(): array
    {
        return [
            'type' => 'multiple_choice',
            'question' => 'ما هي مساحة المربع؟',
            'options' => ['8 سم²', '16 سم²'],
            'correct_index' => 1,
            'explanation' => 'مساحة المربع = الضلع × نفسه',
        ];
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
