<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\InteractiveLessons\Pages\CreateInteractiveLesson;
use App\Filament\Resources\InteractiveLessons\Pages\EditInteractiveLesson;
use App\Filament\Resources\InteractiveLessons\Pages\ListInteractiveLessons;
use App\Filament\Resources\InteractiveLessons\RelationManagers\StationsRelationManager;
use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveLessonFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_list_interactive_lessons(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create([
            'title' => 'مادة العدد 3',
            'is_published' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateInteractiveLesson::class)
            ->fillForm([
                'learning_material_id' => $material->id,
                'lesson_key' => 'ar-g1-math-number-3-admin',
                'title' => 'العدد 3',
                'subtitle' => 'درس تجريبي',
                'subject_code' => 'MATH',
                'grade_level' => 1,
                'station_count' => 6,
                'status' => 'draft',
                'intro_audio_path' => 'audio/grade1/lessons/number-3/intro.mp3',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $lesson = InteractiveLesson::query()
            ->where('lesson_key', 'ar-g1-math-number-3-admin')
            ->firstOrFail();

        $this->assertDatabaseHas('interactive_lessons', [
            'id' => $lesson->id,
            'title' => 'العدد 3',
            'subject_code' => 'MATH',
            'status' => 'draft',
            'station_count' => 6,
            'intro_audio_path' => 'audio/grade1/lessons/number-3/intro.mp3',
        ]);

        Livewire::actingAs($admin)
            ->test(ListInteractiveLessons::class)
            ->assertCanSeeTableRecords([$lesson])
            ->assertSee('العدد 3')
            ->assertSee('MATH')
            ->assertSee('مسودة');
    }

    public function test_admin_can_create_station_with_sequence_pop_config_json(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();
        $lesson = InteractiveLesson::query()->create([
            'learning_material_id' => $material->id,
            'lesson_key' => 'ar-g1-filament-station-test',
            'title' => 'اختبار المحطات',
            'subtitle' => null,
            'subject_code' => 'MATH',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'draft',
            'intro_audio_path' => null,
            'meta' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(StationsRelationManager::class, [
                'ownerRecord' => $lesson,
                'pageClass' => EditInteractiveLesson::class,
            ])
            ->callTableAction('create', data: [
                'station_number' => 2,
                'station_type' => 'sequence_pop',
                'title' => 'عدّ بالترتيب',
                'instructions' => 'افقع 1 ثم 2 ثم 3',
                'sonbol_prompt' => 'هيا نعد!',
                'is_skippable' => false,
                'order_column' => 2,
                'config' => [
                    'target_word' => '123',
                    'syllables' => [
                        ['glyph' => '1', 'order' => 1],
                        ['glyph' => '2', 'order' => 2],
                        ['glyph' => '3', 'order' => 3],
                    ],
                    'completion_audio_script' => 'أحسنت!',
                ],
            ])
            ->assertHasNoTableActionErrors();

        $station = InteractiveLessonStation::query()
            ->where('interactive_lesson_id', $lesson->id)
            ->where('station_number', 2)
            ->firstOrFail();

        $this->assertSame('sequence_pop', $station->station_type);
        $this->assertSame('123', $station->config['target_word'] ?? null);
        $this->assertSame(['1', '2', '3'], array_column($station->config['syllables'] ?? [], 'glyph'));
    }

    public function test_admin_can_update_station_config_for_variant_matrix(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();
        $lesson = InteractiveLesson::query()->create([
            'learning_material_id' => $material->id,
            'lesson_key' => 'ar-g1-filament-variant-test',
            'title' => 'اختبار المصفوفة',
            'subtitle' => null,
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'published',
            'intro_audio_path' => null,
            'meta' => null,
        ]);

        $station = InteractiveLessonStation::query()->create([
            'interactive_lesson_id' => $lesson->id,
            'station_number' => 1,
            'station_type' => 'variant_matrix',
            'title' => 'الحركات',
            'instructions' => null,
            'sonbol_prompt' => null,
            'config' => ['tabs' => []],
            'assets' => null,
            'is_skippable' => false,
            'order_column' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(StationsRelationManager::class, [
                'ownerRecord' => $lesson,
                'pageClass' => EditInteractiveLesson::class,
            ])
            ->callTableAction('edit', $station, data: [
                'station_number' => 1,
                'station_type' => 'variant_matrix',
                'title' => 'الحركات الثلاث',
                'instructions' => 'استمع للحركات',
                'sonbol_prompt' => 'هيا!',
                'is_skippable' => false,
                'order_column' => 1,
                'config' => [
                    'tabs' => [
                        [
                            'glyph' => 'رَ',
                            'label' => 'فتحة',
                            'cards' => [
                                [
                                    'word' => 'رَمَل',
                                    'emoji' => '🏖️',
                                    'highlight' => 'رَ',
                                    'audio_script' => 'رَ مثل رَمَل',
                                ],
                            ],
                        ],
                    ],
                    'voice_targets' => ['رَ'],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $station->refresh();

        $this->assertSame('الحركات الثلاث', $station->title);
        $this->assertSame('رَ', $station->config['tabs'][0]['glyph'] ?? null);
        $this->assertSame('رَمَل', $station->config['tabs'][0]['cards'][0]['word'] ?? null);
        $this->assertEquals(['رَ'], $station->config['voice_targets'] ?? null);
    }

    public function test_admin_can_edit_interactive_lesson_metadata(): void
    {
        $admin = User::factory()->admin()->create();
        $material = LearningMaterial::factory()->create();
        $lesson = InteractiveLesson::query()->create([
            'learning_material_id' => $material->id,
            'lesson_key' => 'ar-g1-filament-edit-meta',
            'title' => 'عنوان قديم',
            'subtitle' => 'قديم',
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'draft',
            'intro_audio_path' => null,
            'meta' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(EditInteractiveLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm([
                'learning_material_id' => $material->id,
                'lesson_key' => 'ar-g1-filament-edit-meta',
                'title' => 'عنوان محدّث',
                'subtitle' => 'جديد',
                'subject_code' => 'AR',
                'grade_level' => 1,
                'station_count' => 6,
                'status' => 'published',
                'intro_audio_path' => 'audio/intro.mp3',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('interactive_lessons', [
            'id' => $lesson->id,
            'title' => 'عنوان محدّث',
            'status' => 'published',
            'intro_audio_path' => 'audio/intro.mp3',
        ]);
    }
}
