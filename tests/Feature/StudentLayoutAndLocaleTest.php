<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLayoutAndLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_student_hub_uses_dedicated_layout_without_parent_links(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان']);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('data-student-layout', false);

        $response->assertDontSee(route('parent.analytics'), false);
        $response->assertDontSee(route('parent.comparative-analytics'), false);
        $response->assertDontSee(route('students.index'), false);
        $response->assertDontSee(route('dashboard'), false);

        $response->assertSee(route('student.dashboard'), false);
        $response->assertSee(route('student.activities.index'), false);
        $response->assertSee(route('student.progress'), false);
        $response->assertSee(route('student.badges'), false);

        $response->assertDontSee('Analytics');
        $response->assertDontSee('Compare');
        $response->assertDontSee('Children');
        $response->assertDontSee('التحليلات');
        $response->assertDontSee('مقارنة');
        $response->assertDontSee('الأبناء');
    }

    public function test_student_routes_enforce_arabic_locale_and_rtl(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'أحمد']);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $this->assertSame('ar', app()->getLocale());

        $response->assertSee('الأنشطة');
        $response->assertSee('تقدمي');
        $response->assertSee('جوائزي');
        $response->assertSee('الخريطة');
        $response->assertSee('مسار تقدمي');
        $response->assertSee('خزانة الجوائز');
    }
}
