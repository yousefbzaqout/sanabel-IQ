<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Models\Subject;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateStationAudioCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(BadgeSeeder::class);

        Http::fake([
            'tahadz.com/*' => Http::response(
                ['result' => 'رَ مِثْلُ رَمَل'],
                200,
                ['Content-Type' => 'application/json'],
            ),
            'translate.google.com/*' => Http::response(
                hex2bin('fff384c4').str_repeat("\0", 256),
                200,
                ['Content-Type' => 'audio/mpeg'],
            ),
        ]);
    }

    public function test_generate_station_audio_command_fills_missing_audio_paths_from_scripts(): void
    {
        $this->seedLetterRaaLesson();

        $station = InteractiveLessonStation::query()
            ->where('station_number', 1)
            ->firstOrFail();

        $this->assertNull($station->config['tabs'][0]['cards'][0]['audio_path'] ?? null);
        $this->assertNotSame('', $station->config['tabs'][0]['cards'][0]['audio_script'] ?? '');

        $exitCode = Artisan::call('curriculum:generate-station-audio', [
            '--lesson' => 'ar-g1-letter-raa',
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());

        $station->refresh();
        $cardPath = $station->config['tabs'][0]['cards'][0]['audio_path'] ?? null;

        $this->assertNotNull($cardPath);
        $this->assertStringStartsWith('audio/grade1/lessons/ar-g1-letter-raa/', $cardPath);
        $this->assertTrue(Storage::disk('public')->exists($cardPath));

        $sequence = InteractiveLessonStation::query()
            ->where('station_number', 2)
            ->firstOrFail();

        $this->assertNotNull($sequence->config['completion_audio_path'] ?? null);
        $this->assertTrue(
            Storage::disk('public')->exists((string) $sequence->config['completion_audio_path']),
        );
    }

    public function test_curriculum_generate_audio_with_lesson_option_updates_station_jsonb(): void
    {
        $this->seedLetterRaaLesson();

        $exitCode = Artisan::call('curriculum:generate-audio', [
            '--lesson' => 'ar-g1-letter-raa',
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());

        $trace = InteractiveLessonStation::query()
            ->where('station_number', 4)
            ->firstOrFail();

        $this->assertNotNull($trace->config['complete_audio_path'] ?? null);
        $this->assertTrue(
            Storage::disk('public')->exists((string) $trace->config['complete_audio_path']),
        );
    }

    public function test_command_skips_existing_paths_unless_force(): void
    {
        $this->seedLetterRaaLesson();

        $station = InteractiveLessonStation::query()->where('station_number', 6)->firstOrFail();
        $config = $station->config;
        $config['explain_script'] = 'شرح تجريبي';
        $config['explain_audio_path'] = 'audio/grade1/lessons/ar-g1-letter-raa/stations/preset-explain.mp3';
        $station->forceFill(['config' => $config])->save();

        Storage::disk('public')->put(
            'audio/grade1/lessons/ar-g1-letter-raa/stations/preset-explain.mp3',
            hex2bin('fff384c4').str_repeat("\0", 128),
        );

        Artisan::call('curriculum:generate-station-audio', [
            '--lesson' => 'ar-g1-letter-raa',
        ]);

        $station->refresh();
        $this->assertSame(
            'audio/grade1/lessons/ar-g1-letter-raa/stations/preset-explain.mp3',
            $station->config['explain_audio_path'],
        );

        $before = Storage::disk('public')->get(
            'audio/grade1/lessons/ar-g1-letter-raa/stations/preset-explain.mp3',
        );

        Artisan::call('curriculum:generate-station-audio', [
            '--lesson' => 'ar-g1-letter-raa',
            '--force' => true,
        ]);

        $after = Storage::disk('public')->get(
            'audio/grade1/lessons/ar-g1-letter-raa/stations/preset-explain.mp3',
        );

        $this->assertNotSame('', $after);
        $this->assertSame(strlen($before) > 0, strlen($after) > 0);
    }

    private function seedLetterRaaLesson(): void
    {
        $subject = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);

        LearningMaterial::factory()->for($subject)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
        ]);

        app(LetterRaaInteractiveLessonImporter::class)->import();

        $this->assertSame(1, InteractiveLesson::query()->where('lesson_key', 'ar-g1-letter-raa')->count());
    }
}
