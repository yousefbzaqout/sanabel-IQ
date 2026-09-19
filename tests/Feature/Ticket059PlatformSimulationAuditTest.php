<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Student\LearningMapService;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ticket059PlatformSimulationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        $this->seed(B2bDemoTenantSeeder::class);
    }

    public function test_seeded_roles_redirect_to_correct_dashboards_after_login(): void
    {
        $this->post('/login', [
            'email' => B2bDemoTenantSeeder::TEACHER_EMAIL,
            'password' => B2bDemoTenantSeeder::PASSWORD,
            'intended_role' => 'teacher',
        ])->assertRedirect('/teacher');

        $this->post('/logout');

        $this->post('/login', [
            'email' => B2bDemoTenantSeeder::STUDENT_EMAIL,
            'password' => B2bDemoTenantSeeder::PASSWORD,
            'intended_role' => 'parent',
        ])->assertRedirect('/parent');

        $this->post('/logout');

        $this->post('/login', [
            'email' => B2bDemoTenantSeeder::ADMIN_EMAIL,
            'password' => B2bDemoTenantSeeder::PASSWORD,
            'intended_role' => 'admin',
        ])->assertRedirect('/admin');
    }

    public function test_login_role_tabs_expose_intended_role_payload_without_overriding_auth(): void
    {
        $login = $this->get('/login');
        $login->assertOk();
        $login->assertSee('أولياء الأمور', false);
        $login->assertSee('الطلاب والأبناء', false);
        $login->assertSee('رمز العائلة', false);
        $login->assertSee('name="intended_role"', false);
        $login->assertDontSee('المعلمون والكادر', false);
        $login->assertDontSee('حساب إدارة المدرسة', false);
        $login->assertDontSee('admin_panel_settings', false);

        // Wrong tab + correct credentials still redirect by actual account role.
        $this->post('/login', [
            'email' => B2bDemoTenantSeeder::TEACHER_EMAIL,
            'password' => B2bDemoTenantSeeder::PASSWORD,
            'intended_role' => 'student',
        ])->assertRedirect('/teacher');
    }

    public function test_landing_page_has_section_anchors_legal_links_and_2026_footer(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('المميزات', false);
        $response->assertSee('رحلة التعلم', false);
        $response->assertSee('حلول المدارس', false);
        $response->assertSee('استعراض الأدوار', false);
        $response->assertSee('الأسعار والاشتراكات', false);

        $response->assertSee('id="features"', false);
        $response->assertSee('id="learning-journey"', false);
        $response->assertSee('id="schools"', false);
        $response->assertSee('id="roles"', false);
        $response->assertSee('id="pricing"', false);

        $response->assertSee('href="#features"', false);
        $response->assertSee('href="#learning-journey"', false);
        $response->assertSee('href="#schools"', false);
        $response->assertSee('href="#roles"', false);
        $response->assertSee('href="#pricing"', false);

        $response->assertSee(
            '© 2026 سنابل IQ - جميع الحقوق محفوظة لشركة سنابل للحلول التعليمية',
            false,
        );

        $response->assertSee(route('legal.privacy'), false);
        $response->assertSee(route('legal.terms'), false);
        $response->assertSee(route('legal.compliance'), false);
    }

    public function test_legal_compliance_pages_are_accessible(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('سياسة الخصوصية الأكاديمية', false);

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('شروط الخدمة للمدارس', false);

        $this->get(route('legal.compliance'))
            ->assertOk()
            ->assertSee('خارطة الحماية والامتثال', false);
    }

    public function test_pronunciation_and_stroke_ai_endpoints_accept_authenticated_student_input(): void
    {
        $parent = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::STUDENT_EMAIL)
            ->firstOrFail();
        $student = Student::query()
            ->where('user_id', $parent->id)
            ->firstOrFail();

        $arabic = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);
        LearningMaterial::factory()->for($arabic)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
            'order_column' => 1,
        ]);
        app(LetterRaaInteractiveLessonImporter::class)->import();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
                'lesson_key' => LetterRaaInteractiveLessonImporter::LESSON_KEY,
            ])
            ->assertOk()
            ->assertJsonStructure(['result', 'feedback']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.stroke'), [
                'lesson_key' => LetterRaaInteractiveLessonImporter::LESSON_KEY,
                'points' => [
                    ['x' => 40, 'y' => 30],
                    ['x' => 55, 'y' => 45],
                    ['x' => 70, 'y' => 70],
                    ['x' => 85, 'y' => 95],
                    ['x' => 95, 'y' => 110],
                ],
            ])
            ->assertOk()
            ->assertJsonStructure(['result']);

        // Dedicated GET station pages are not routed (POST-only AI endpoints → 405).
        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/ai/pronunciation')
            ->assertStatus(405);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/ai/stroke')
            ->assertStatus(405);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.interactive-lesson.show', [
                'lessonKey' => LetterRaaInteractiveLessonImporter::LESSON_KEY,
            ]))
            ->assertOk()
            ->assertSee('data-voice-mic', false)
            ->assertSee('data-trace-canvas', false);
    }

    public function test_quiz_completion_awards_xp_and_unlocks_next_learning_map_stage(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'grade_level' => 1,
            'total_xp' => 0,
        ]);

        $subjectA = Subject::factory()->create([
            'name' => 'مرحلة أ',
            'grade_level' => 1,
            'code' => 'STAGE-A-G1',
        ]);
        $subjectB = Subject::factory()->create([
            'name' => 'مرحلة ب',
            'grade_level' => 1,
            'code' => 'STAGE-B-G1',
        ]);

        $materialA = LearningMaterial::factory()->published()->create([
            'subject_id' => $subjectA->id,
            'title' => 'تحدي المرحلة أ',
            'xp_reward' => 100,
            'order_column' => 1,
        ]);
        LearningMaterial::factory()->published()->create([
            'subject_id' => $subjectB->id,
            'title' => 'تحدي المرحلة ب',
            'xp_reward' => 100,
            'order_column' => 1,
        ]);

        $map = app(LearningMapService::class);
        $before = collect($map->buildNodes($student))->keyBy('title');
        $this->assertSame('available', $before['مرحلة أ']['state']);
        $this->assertSame('locked', $before['مرحلة ب']['state']);

        $questions = $this->createQuizQuestions($materialA);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $materialA), [
                'answers' => [
                    [
                        'question_id' => $questions[0]['question']->id,
                        'selected_option_id' => $questions[0]['correct']->id,
                    ],
                    [
                        'question_id' => $questions[1]['question']->id,
                        'selected_option_id' => $questions[1]['correct']->id,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('xp_earned', 100);

        $student->refresh();
        $this->assertSame(100, $student->total_xp);

        $after = collect($map->buildNodes($student->fresh()))->keyBy('title');
        $this->assertSame('completed', $after['مرحلة أ']['state']);
        $this->assertSame('available', $after['مرحلة ب']['state']);

        $this->actingAs($parent)
            ->withSession([
                'active_student_id' => $student->id,
                'quiz_celebration' => [
                    'learning_material_id' => $materialA->id,
                    'xp_earned' => 100,
                    'percentage' => 100,
                    'streak_days' => 1,
                    'badge_ids' => [],
                ],
            ])
            ->get(route('student.quiz.completion', $materialA))
            ->assertOk();
    }

    /**
     * @return list<array{question: Question, correct: QuestionOption, incorrect: QuestionOption}>
     */
    private function createQuizQuestions(LearningMaterial $material): array
    {
        $rows = [];

        for ($i = 1; $i <= 2; $i++) {
            $question = Question::factory()->for($material)->create([
                'prompt' => "سؤال {$i}",
                'order_column' => $i,
                'points' => 10,
            ]);
            $correct = QuestionOption::factory()->for($question)->create([
                'option_text' => 'صحيح',
                'is_correct' => true,
            ]);
            $incorrect = QuestionOption::factory()->for($question)->create([
                'option_text' => 'خطأ',
                'is_correct' => false,
            ]);

            $rows[] = [
                'question' => $question,
                'correct' => $correct,
                'incorrect' => $incorrect,
            ];
        }

        return $rows;
    }
}
