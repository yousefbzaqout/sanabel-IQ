<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Filament\Resources\LearningMaterials\Pages\CreateLearningMaterial;
use App\Filament\Resources\LearningMaterials\Pages\EditLearningMaterial;
use App\Filament\Resources\LearningMaterials\Pages\ListLearningMaterials;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_access_filament_admin_panel(): void
    {
        $parent = User::factory()->create(['role' => UserRole::Parent]);

        $this->get('/admin')
            ->assertRedirect('/admin/login');

        $this->actingAs($parent)
            ->get('/admin')
            ->assertForbidden();

        Livewire::actingAs($parent)
            ->test(ListSubjects::class)
            ->assertForbidden();

        $this->actingAs($parent)
            ->get('/admin/subjects')
            ->assertForbidden();
    }

    public function test_admin_can_manage_subjects_via_filament_resource(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateSubject::class)
            ->fillForm([
                'name' => 'رياضيات',
                'code' => 'MATH-G3',
                'grade_level' => 3,
                'description' => 'منهج الصف الثالث',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subjects', [
            'name' => 'رياضيات',
            'code' => 'MATH-G3',
            'grade_level' => 3,
        ]);

        $subject = Subject::query()->where('code', 'MATH-G3')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ListSubjects::class)
            ->set('activeTab', 'grade_3')
            ->assertCanSeeTableRecords([$subject]);

        Livewire::actingAs($admin)
            ->test(EditSubject::class, ['record' => $subject->getRouteKey()])
            ->fillForm([
                'name' => 'رياضيات متقدمة',
                'code' => 'MATH-G3',
                'grade_level' => 3,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'رياضيات متقدمة',
        ]);
    }

    public function test_admin_can_manage_learning_materials_and_toggle_publication_status(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create([
            'name' => 'علوم',
            'code' => 'SCI-G2',
            'grade_level' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateLearningMaterial::class)
            ->fillForm([
                'subject_id' => $subject->id,
                'title' => 'وحدة الكائنات الحية',
                'description' => 'مقدمة عن النباتات',
                'xp_reward' => 100,
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $material = LearningMaterial::query()->where('title', 'وحدة الكائنات الحية')->firstOrFail();

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'subject_id' => $subject->id,
            'is_published' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(EditLearningMaterial::class, ['record' => $material->getRouteKey()])
            ->fillForm([
                'subject_id' => $subject->id,
                'title' => 'وحدة الكائنات الحية',
                'xp_reward' => 100,
                'is_published' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($material->fresh()?->is_published);
    }

    public function test_admin_can_create_mcq_and_true_false_questions_with_options_repeater(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateQuestion::class)
            ->fillForm([
                'learning_material_id' => $material->id,
                'type' => QuestionType::Mcq->value,
                'prompt' => 'ما حاصل 2 + 3؟',
                'explanation' => '2 + 3 = 5',
                'points' => 15,
                'options' => [
                    ['option_text' => '4', 'is_correct' => false],
                    ['option_text' => '5', 'is_correct' => true],
                    ['option_text' => '6', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $mcqQuestion = Question::query()
            ->where('learning_material_id', $material->id)
            ->where('type', QuestionType::Mcq)
            ->firstOrFail();

        $this->assertSame('2 + 3 = 5', $mcqQuestion->explanation);
        $this->assertDatabaseHas('question_options', [
            'question_id' => $mcqQuestion->id,
            'option_text' => '5',
            'is_correct' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateQuestion::class)
            ->fillForm([
                'learning_material_id' => $material->id,
                'type' => QuestionType::TrueFalse->value,
                'prompt' => 'الشمس تشرق من الشرق؟',
                'explanation' => 'الشمس تشرق من الشرق دائماً.',
                'points' => 10,
                'options' => [
                    ['option_text' => 'صح', 'is_correct' => true],
                    ['option_text' => 'خطأ', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $trueFalseQuestion = Question::query()
            ->where('learning_material_id', $material->id)
            ->where('type', QuestionType::TrueFalse)
            ->firstOrFail();

        $this->assertSame(2, QuestionOption::query()->where('question_id', $trueFalseQuestion->id)->count());
    }

    public function test_parent_cannot_invoke_filament_livewire_update_endpoints(): void
    {
        $parent = User::factory()->create(['role' => UserRole::Parent]);

        Livewire::actingAs($parent)
            ->test(CreateQuestion::class)
            ->assertForbidden();

        Livewire::actingAs($parent)
            ->test(ListLearningMaterials::class)
            ->assertForbidden();

        Livewire::actingAs($parent)
            ->test(EditLearningMaterial::class, [
                'record' => LearningMaterial::factory()->create()->getRouteKey(),
            ])
            ->assertForbidden();

        // Panel-scoped Livewire update path must not exist as an open hijack surface.
        $this->actingAs($parent)
            ->post('/admin/livewire/update', [
                'components' => [
                    [
                        'snapshot' => '{}',
                        'updates' => [],
                        'calls' => [],
                    ],
                ],
            ])
            ->assertNotFound();
    }

    public function test_filament_mcq_creation_rejects_zero_correct_options_with_arabic_error(): void
    {
        app()->setLocale('ar');

        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateQuestion::class)
            ->fillForm([
                'learning_material_id' => $material->id,
                'type' => QuestionType::Mcq->value,
                'prompt' => 'ما حاصل 2 + 3؟',
                'explanation' => 'يجب اختيار إجابة صحيحة',
                'points' => 10,
                'options' => [
                    ['option_text' => '4', 'is_correct' => false],
                    ['option_text' => '5', 'is_correct' => false],
                    ['option_text' => '6', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['options'])
            ->assertSee('يجب تحديد خيار واحد على الأقل كإجابة صحيحة');

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }

    public function test_deleting_learning_material_via_filament_cascades_questions_and_options(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $questionIds = [];
        $optionIds = [];

        for ($questionIndex = 0; $questionIndex < 2; $questionIndex++) {
            $question = Question::factory()->for($material)->mcq()->create([
                'prompt' => 'سؤال '.$questionIndex,
                'order_column' => $questionIndex,
            ]);
            $questionIds[] = $question->id;

            for ($optionIndex = 0; $optionIndex < 3; $optionIndex++) {
                $option = QuestionOption::factory()->for($question)->create([
                    'option_text' => 'خيار '.$optionIndex,
                    'is_correct' => $optionIndex === 0,
                    'order_column' => $optionIndex,
                ]);
                $optionIds[] = $option->id;
            }
        }

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('question_options', 6);

        Livewire::actingAs($admin)
            ->test(EditLearningMaterial::class, ['record' => $material->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('learning_materials', ['id' => $material->id]);

        foreach ($questionIds as $questionId) {
            $this->assertDatabaseMissing('questions', ['id' => $questionId]);
        }

        foreach ($optionIds as $optionId) {
            $this->assertDatabaseMissing('question_options', ['id' => $optionId]);
        }

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }
}
