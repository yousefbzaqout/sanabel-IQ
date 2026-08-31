<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentMaterialUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('materials');
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
}
