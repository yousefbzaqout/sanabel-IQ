<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use Tests\TestCase;

class LandingPageRealismTest extends TestCase
{
    public function test_landing_page_renders_with_aligned_demo_ctas(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $response->assertSee('id="demo-request-section"', false);
        $response->assertSee('id="demo-request-form"', false);
        $response->assertSee('اطلب عرضاً تجريبياً', false);
        $response->assertSee('ابدأ تجربة المدرسة', false);
        $response->assertSee('data-demo-plan="standard"', false);
        $response->assertSee('data-demo-plan="growth"', false);
        $response->assertSee('data-demo-plan="enterprise"', false);
        $response->assertSee('name="selected_plan"', false);

        $response->assertSee('مصمم للمدارس الرائدة والنموذجية', false);
        $response->assertDontSee('أكثر من 25 مدرسة', false);

        $response->assertSee('قريباً', false);
        $response->assertSee('التكامل البرمجي مع الوزارة', false);

        $previewUrl = route('preview.interactive-lesson', [
            'lessonKey' => LetterRaaInteractiveLessonImporter::LESSON_KEY,
        ]);
        $response->assertSee($previewUrl, false);
        $response->assertSee('تجربة تفاعلية سريعة', false);
    }

    public function test_public_interactive_lesson_preview_is_available_without_auth(): void
    {
        $response = $this->get(route('preview.interactive-lesson', [
            'lessonKey' => LetterRaaInteractiveLessonImporter::LESSON_KEY,
        ]));

        $response->assertOk();
        $response->assertSee(LetterRaaInteractiveLessonImporter::LESSON_KEY, false);
    }
}
