<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuestionBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_questions(): void
    {
        $parent = User::factory()->create(['role' => UserRole::Parent]);
        $material = LearningMaterial::factory()->create();

        $this->actingAs($parent)
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::Mcq->value,
                'prompt' => 'ما حاصل 2 + 3؟',
                'points' => 15,
                'options' => [
                    ['option_text' => '5', 'is_correct' => true],
                    ['option_text' => '4', 'is_correct' => false],
                ],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_mcq_question_with_options(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.materials.questions.store', $material), [
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
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'learning_material_id' => $material->id,
            'type' => QuestionType::Mcq->value,
            'prompt' => 'ما حاصل 2 + 3؟',
            'explanation' => '2 + 3 = 5',
            'points' => 15,
        ]);

        $question = Question::query()->where('learning_material_id', $material->id)->firstOrFail();

        $this->assertDatabaseCount('question_options', 3);
        $this->assertDatabaseHas('question_options', [
            'question_id' => $question->id,
            'option_text' => '5',
            'is_correct' => true,
        ]);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_mcq_question_creation_fails_without_at_least_one_correct_option(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $material))
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::Mcq->value,
                'prompt' => 'ما حاصل 2 + 3؟',
                'points' => 10,
                'options' => [
                    ['option_text' => '1', 'is_correct' => false],
                    ['option_text' => '2', 'is_correct' => false],
                    ['option_text' => '3', 'is_correct' => false],
                    ['option_text' => '4', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('options')
            ->assertRedirect();

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }

    public function test_admin_can_update_and_delete_question(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();
        $question = Question::factory()->for($material)->mcq()->create([
            'prompt' => 'سؤال قديم',
            'points' => 10,
        ]);
        QuestionOption::factory()->for($question)->create([
            'option_text' => 'خطأ',
            'is_correct' => false,
            'order_column' => 0,
        ]);
        QuestionOption::factory()->for($question)->create([
            'option_text' => 'صحيح',
            'is_correct' => true,
            'order_column' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update', $question), [
                'type' => QuestionType::Mcq->value,
                'prompt' => 'سؤال محدث',
                'explanation' => 'شرح محدث',
                'points' => 20,
                'options' => [
                    ['option_text' => 'خيار أ', 'is_correct' => false],
                    ['option_text' => 'خيار ب', 'is_correct' => true],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'prompt' => 'سؤال محدث',
            'explanation' => 'شرح محدث',
            'points' => 20,
        ]);
        $this->assertSame(2, $question->options()->count());
        $this->assertTrue($question->options()->where('option_text', 'خيار ب')->where('is_correct', true)->exists());

        $this->actingAs($admin)
            ->delete(route('admin.questions.destroy', $question))
            ->assertRedirect();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['question_id' => $question->id]);
    }

    public function test_admin_can_reorder_questions_within_material(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $first = Question::factory()->for($material)->create(['prompt' => 'Q1', 'order_column' => 0]);
        $second = Question::factory()->for($material)->create(['prompt' => 'Q2', 'order_column' => 1]);
        $third = Question::factory()->for($material)->create(['prompt' => 'Q3', 'order_column' => 2]);

        $this->actingAs($admin)
            ->post(route('admin.materials.questions.reorder', $material), [
                'ordered_ids' => [$third->id, $first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertSame(0, $third->fresh()?->order_column);
        $this->assertSame(1, $first->fresh()?->order_column);
        $this->assertSame(2, $second->fresh()?->order_column);
    }

    public function test_true_false_question_rejects_three_options(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $material))
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::TrueFalse->value,
                'prompt' => 'الشمس تشرق من الشرق؟',
                'points' => 10,
                'options' => [
                    ['option_text' => 'صح', 'is_correct' => true],
                    ['option_text' => 'خطأ', 'is_correct' => false],
                    ['option_text' => 'لا أعرف', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('options')
            ->assertRedirect();

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }

    public function test_true_false_question_rejects_zero_correct_options(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $material))
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::TrueFalse->value,
                'prompt' => 'الشمس تشرق من الشرق؟',
                'points' => 10,
                'options' => [
                    ['option_text' => 'صح', 'is_correct' => false],
                    ['option_text' => 'خطأ', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('options')
            ->assertRedirect();

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }

    public function test_mcq_question_rejects_empty_or_whitespace_only_option_text(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $material))
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::Mcq->value,
                'prompt' => 'ما حاصل 2 + 3؟',
                'points' => 10,
                'options' => [
                    ['option_text' => '', 'is_correct' => true],
                    ['option_text' => '5', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('options.0.option_text')
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $material))
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::Mcq->value,
                'prompt' => 'ما حاصل 2 + 3؟',
                'points' => 10,
                'options' => [
                    ['option_text' => '   ', 'is_correct' => true],
                    ['option_text' => '5', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('options.0.option_text')
            ->assertRedirect();

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_options', 0);
    }

    public function test_deleting_material_cascades_questions_and_options(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();

        $questionIds = [];
        $optionIds = [];

        for ($questionIndex = 0; $questionIndex < 3; $questionIndex++) {
            $question = Question::factory()->for($material)->mcq()->create([
                'prompt' => 'سؤال '.$questionIndex,
                'order_column' => $questionIndex,
            ]);
            $questionIds[] = $question->id;

            for ($optionIndex = 0; $optionIndex < 4; $optionIndex++) {
                $option = QuestionOption::factory()->for($question)->create([
                    'option_text' => 'خيار '.$optionIndex,
                    'is_correct' => $optionIndex === 0,
                    'order_column' => $optionIndex,
                ]);
                $optionIds[] = $option->id;
            }
        }

        $this->assertDatabaseCount('questions', 3);
        $this->assertDatabaseCount('question_options', 12);

        $this->actingAs($admin)
            ->delete(route('admin.materials.destroy', $material))
            ->assertRedirect();

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

    public function test_question_prompt_and_explanation_escape_malicious_html_on_render(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();
        $xssPrompt = "<script>alert('xss')</script><b>ما عاصمة فلسطين؟</b>";
        $xssExplanation = "<script>alert('explanation')</script>القدس";

        $this->actingAs($admin)
            ->post(route('admin.materials.questions.store', $material), [
                'type' => QuestionType::Mcq->value,
                'prompt' => $xssPrompt,
                'explanation' => $xssExplanation,
                'points' => 10,
                'options' => [
                    ['option_text' => 'القدس', 'is_correct' => true],
                    ['option_text' => 'عمان', 'is_correct' => false],
                ],
            ])
            ->assertRedirect();

        $question = Question::query()->where('learning_material_id', $material->id)->firstOrFail();
        $this->assertSame($xssPrompt, $question->prompt);
        $this->assertSame($xssExplanation, $question->explanation);

        $this->actingAs($admin)
            ->get(route('admin.materials.questions.index', $material))
            ->assertOk()
            ->assertDontSee($xssPrompt, false)
            ->assertDontSee($xssExplanation, false)
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;&lt;b&gt;ما عاصمة فلسطين؟&lt;/b&gt;', false)
            ->assertSee('ما عاصمة فلسطين؟', false)
            ->assertSee('&lt;script&gt;alert(&#039;explanation&#039;)&lt;/script&gt;القدس', false)
            ->assertSee('القدس', false);
    }

    public function test_reorder_rejects_cross_material_question_ids_without_corrupting_order(): void
    {
        $admin = User::factory()->admin()->create();
        $materialA = LearningMaterial::factory()->create();
        $materialB = LearningMaterial::factory()->create();

        $a1 = Question::factory()->for($materialA)->create(['prompt' => 'A1', 'order_column' => 0]);
        $a2 = Question::factory()->for($materialA)->create(['prompt' => 'A2', 'order_column' => 1]);
        $b1 = Question::factory()->for($materialB)->create(['prompt' => 'B1', 'order_column' => 0]);

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $materialA))
            ->post(route('admin.materials.questions.reorder', $materialA), [
                'ordered_ids' => [$a2->id, $b1->id],
            ])
            ->assertSessionHasErrors('ordered_ids')
            ->assertRedirect();

        $this->assertSame(0, $a1->fresh()?->order_column);
        $this->assertSame(1, $a2->fresh()?->order_column);
        $this->assertSame(0, $b1->fresh()?->order_column);

        $this->actingAs($admin)
            ->from(route('admin.materials.questions.index', $materialA))
            ->post(route('admin.materials.questions.reorder', $materialA), [
                'ordered_ids' => [$a2->id],
            ])
            ->assertSessionHasErrors('ordered_ids')
            ->assertRedirect();

        $this->assertSame(0, $a1->fresh()?->order_column);
        $this->assertSame(1, $a2->fresh()?->order_column);
    }
}
