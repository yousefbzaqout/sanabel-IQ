<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Jobs\ProcessPDFMaterialJob;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentMaterialUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('materials');
        Queue::fake();
    }

    public function test_parent_can_upload_pdf_material_for_active_child(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $file = UploadedFile::fake()->create('math_exam.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('materials.store'), [
                'title' => 'Math Unit 1 Exam',
                'type' => 'exam',
                'file' => $file,
            ]);

        $response->assertRedirect(route('dashboard'));

        $material = ParentMaterial::query()->first();
        $this->assertNotNull($material);
        $this->assertSame('Math Unit 1 Exam', $material->title);
        $this->assertSame(MaterialType::Exam, $material->type);
        $this->assertSame(MaterialStatus::Pending, $material->status);
        $this->assertSame($parent->id, $material->user_id);
        $this->assertSame($student->id, $material->student_id);

        Storage::disk('materials')->assertExists($material->file_path);
        $this->assertStringEndsWith('.pdf', $material->file_path);
        Queue::assertPushed(ProcessPDFMaterialJob::class, fn (ProcessPDFMaterialJob $job): bool => $job->parentMaterial->is($material));
    }

    public function test_upload_fails_for_non_pdf_file_types(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->from(route('dashboard'))
            ->post(route('materials.store'), [
                'title' => 'Photo Upload',
                'type' => 'exam',
                'file' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('file');

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->from(route('dashboard'))
            ->post(route('materials.store'), [
                'title' => 'Word Doc',
                'type' => 'summary',
                'file' => UploadedFile::fake()->create('notes.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('parent_materials', 0);
    }

    public function test_upload_fails_if_file_exceeds_10mb(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->from(route('dashboard'))
            ->post(route('materials.store'), [
                'title' => 'Huge Exam',
                'type' => 'exam',
                'file' => UploadedFile::fake()->create('huge.pdf', 12288, 'application/pdf'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('parent_materials', 0);
    }

    public function test_parent_cannot_upload_material_without_active_child_context(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->post(route('materials.store'), [
                'title' => 'Math Unit 1 Exam',
                'type' => 'exam',
                'file' => UploadedFile::fake()->create('math_exam.pdf', 2048, 'application/pdf'),
            ])
            ->assertRedirect(route('onboarding.child'));

        $this->assertDatabaseCount('parent_materials', 0);
    }

    public function test_parent_cannot_view_or_delete_another_parents_uploaded_material(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();
        Student::factory()->for($parentB)->create();

        $material = ParentMaterial::factory()->for($parentA)->for($studentA)->create([
            'file_path' => 'owned.pdf',
        ]);
        Storage::disk('materials')->put('owned.pdf', 'pdf-content');

        $this->actingAs($parentB)
            ->get(route('materials.show', $material))
            ->assertForbidden();

        $this->actingAs($parentB)
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('parent_materials', ['id' => $material->id]);
        Storage::disk('materials')->assertExists('owned.pdf');
    }

    public function test_deleting_material_removes_file_from_disk_and_database(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => 'delete-me.pdf',
        ]);
        Storage::disk('materials')->put('delete-me.pdf', 'pdf-content');

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->delete(route('materials.destroy', $material))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('parent_materials', ['id' => $material->id]);
        Storage::disk('materials')->assertMissing('delete-me.pdf');
    }

    public function test_upload_rejects_zero_byte_pdf_without_creating_records(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->from(route('dashboard'))
            ->post(route('materials.store'), [
                'title' => 'Empty Exam',
                'type' => 'exam',
                'file' => UploadedFile::fake()->create('empty.pdf', 0, 'application/pdf'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('parent_materials', 0);
        $this->assertSame(0, count(Storage::disk('materials')->allFiles()));
    }

    public function test_upload_rejects_mime_spoofed_pdf_extension(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $jpeg = UploadedFile::fake()->image('probe.jpg');
        $spoofedPdf = new UploadedFile(
            $jpeg->getPathname(),
            'fake_document.pdf',
            'application/pdf',
            null,
            true,
        );

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->from(route('dashboard'))
            ->post(route('materials.store'), [
                'title' => 'Fake Document',
                'type' => 'exam',
                'file' => $spoofedPdf,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('parent_materials', 0);
    }

    public function test_upload_stores_hashed_filename_and_escapes_malicious_titles(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $xssTitle = "<script>alert('xss')</script>";

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('materials.store'), [
                'title' => $xssTitle,
                'type' => 'worksheet',
                'file' => UploadedFile::fake()->create('../../secret_file.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('dashboard'));

        $material = ParentMaterial::query()->first();
        $this->assertNotNull($material);
        $this->assertSame($xssTitle, $material->title);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}\.pdf$/', $material->file_path);
        $this->assertStringNotContainsString('..', $material->file_path);
        $this->assertStringNotContainsString('secret_file', $material->file_path);

        $this->actingAs($parent)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertDontSee($xssTitle, false);

        $this->actingAs($parent)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertDontSee($xssTitle, false);
    }

    public function test_material_files_are_not_publicly_accessible_via_storage_urls(): void
    {
        $filePath = 'inaccessible.pdf';
        Storage::disk('materials')->put($filePath, 'private-pdf-content');

        foreach ([
            '/storage/materials/'.$filePath,
            '/private/materials/'.$filePath,
            '/storage/app/private/materials/'.$filePath,
        ] as $url) {
            $response = $this->get($url);
            $this->assertContains($response->status(), [403, 404], "Expected {$url} to be inaccessible.");
        }
    }

    public function test_parent_b_cannot_access_parent_a_uploaded_material(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();
        Student::factory()->for($parentB)->create();

        $file = UploadedFile::fake()->create('parent_a_exam.pdf', 2048, 'application/pdf');

        $this->actingAs($parentA)
            ->withSession(['active_student_id' => $studentA->id])
            ->post(route('materials.store'), [
                'title' => 'Parent A Exam',
                'type' => 'exam',
                'file' => $file,
            ])
            ->assertRedirect(route('dashboard'));

        $material = ParentMaterial::query()->where('user_id', $parentA->id)->first();
        $this->assertNotNull($material);
        Storage::disk('materials')->assertExists($material->file_path);

        $this->actingAs($parentB)
            ->get(route('materials.show', $material))
            ->assertForbidden();

        $this->actingAs($parentB)
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('parent_materials', ['id' => $material->id]);
        Storage::disk('materials')->assertExists($material->file_path);
    }

    public function test_deleting_child_removes_attached_material_files_from_disk(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => 'child-material.pdf',
        ]);
        Storage::disk('materials')->put('child-material.pdf', 'pdf-content');

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('parent_materials', ['id' => $material->id]);
        Storage::disk('materials')->assertMissing('child-material.pdf');
    }
}
