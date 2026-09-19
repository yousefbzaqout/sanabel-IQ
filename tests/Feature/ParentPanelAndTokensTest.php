<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Providers\Filament\ParentPanelProvider;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ParentPanelAndTokensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_parent_panel_renders_responsive_widgets_and_rtl_attributes(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان']);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/parent');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertDontSee('html { direction: rtl; }', false);
        $response->assertSee('id="active-child-select"', false);
        $response->assertSee('min-h-11', false);
        $response->assertSee('مؤشر إتقان المهارات');
        $response->assertSee('لوحة المتصدرين');
        $response->assertSee('توجيهات سنبل الذكية');
        $response->assertSee('لوحة متابعة الطفل: ليان');

        $providerSource = file_get_contents((new ReflectionClass(ParentPanelProvider::class))->getFileName());
        $this->assertNotFalse($providerSource);
        $this->assertStringNotContainsString('html { direction: rtl; }', $providerSource);
        $this->assertStringContainsString("->font('Tajawal')", $providerSource);
        $this->assertStringContainsString("->viteTheme('resources/css/filament/parent-theme.css')", $providerSource);

        $response->assertSee('bg-surface-container-lowest', false);
        $response->assertSee('Material+Symbols+Outlined', false);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString("'Tajawal'", $css);
        $this->assertStringNotContainsString('Instrument Sans', $css);

        $themeCss = file_get_contents(resource_path('css/filament/parent-theme.css'));
        $this->assertNotFalse($themeCss);
        $this->assertStringContainsString('filament/filament/resources/css/theme.css', $themeCss);
        $this->assertStringContainsString('--color-primary-container: #f59e0b', $themeCss);
        $response->assertSee('data-mastery-chart="empty"', false);
        $response->assertDontSee('Q 180,60', false);
        $response->assertDontSee('M 40,160 Q 180,60 320,80', false);
    }

    public function test_parent_dashboard_mastery_chart_is_driven_by_attempt_data(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان', 'grade_level' => 1]);

        $material = \App\Models\ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create(['title' => 'اللغة العربية', 'status' => \App\Enums\MaterialStatus::Completed]);

        $activity = \App\Models\Activity::factory()
            ->for($student)
            ->create([
                'parent_material_id' => $material->id,
                'status' => \App\Enums\ActivityStatus::Published,
            ]);

        \App\Models\ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'score' => 4,
                'total_questions' => 5,
                'xp_earned' => 20,
                'completed_at' => now()->subDays(2),
            ]);

        \App\Models\ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'score' => 5,
                'total_questions' => 5,
                'xp_earned' => 25,
                'completed_at' => now()->subDay(),
            ]);

        $trend = app(\App\Services\Analytics\SubjectAnalyticsService::class)
            ->accuracyTrend($student, 7);

        $this->assertNotEmpty($trend['points']);
        $this->assertTrue($trend['has_data']);
        $this->assertArrayHasKey('amber_stroke', $trend['paths']);
        $this->assertStringNotContainsString('Q 180,60', $trend['paths']['amber_stroke']);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/parent');

        $response->assertOk();
        $response->assertSee('data-mastery-chart="ready"', false);
        $response->assertSee('اللغة العربية');
        $response->assertSee('90%');
        $response->assertDontSee('Q 180,60', false);
    }

    public function test_student_avatar_component_renders_fallback_initials_when_no_image_exists(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'أحمد',
            'avatar_path' => null,
        ]);

        $html = (string) $this->blade(
            '<x-student.avatar :student="$student" size="md" />',
            ['student' => $student],
        );

        $this->assertStringContainsString('data-student-avatar', $html);
        $this->assertStringContainsString('data-avatar-fallback="true"', $html);
        $this->assertStringContainsString('أ', $html);
        $this->assertStringNotContainsString('<img', $html);

        $withImage = Student::factory()->for($parent)->create([
            'name' => 'سارة',
            'avatar_path' => 'avatars/sara.png',
        ]);

        $imageHtml = (string) $this->blade(
            '<x-student.avatar :student="$student" size="md" />',
            ['student' => $withImage],
        );

        $this->assertStringContainsString('data-student-avatar', $imageHtml);
        $this->assertStringContainsString('<img', $imageHtml);
        $this->assertStringContainsString('avatars/sara.png', $imageHtml);
        $this->assertStringContainsString('data-avatar-fallback="false"', $imageHtml);
    }
}
