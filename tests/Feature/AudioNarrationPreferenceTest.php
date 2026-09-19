<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AudioNarrationPreferenceTest extends TestCase
{
    public function test_live_arabic_speak_prefers_server_neural_tts_over_browser_native(): void
    {
        $source = (string) file_get_contents(resource_path('js/audio-narration.js'));

        $this->assertNotFalse(
            preg_match(
                '/async \(\) => \{.*?await this\.speakViaServer\(text.*?speakNative/s',
                $source,
            ),
            'narrate() must try server (neural) TTS before browser SpeechSynthesis for Arabic.',
        );

        $nativeFirst = preg_match(
            '/if \(hasArabicVoice\(voices\)\) \{\s*const nativeOk = await this\.speakNative/s',
            $source,
        );

        $this->assertSame(
            0,
            $nativeFirst,
            'Browser native TTS must not be preferred over /student/tts for student narration.',
        );

        $this->assertStringContainsString('speakFast', $source);
        $this->assertStringContainsString('serverBudgetMs', $source);
    }

    public function test_quiz_runner_does_not_block_transitions_on_tts_speak_events(): void
    {
        $runner = (string) file_get_contents(app_path('Livewire/Student/QuizRunner.php'));
        $blade = (string) file_get_contents(resource_path('views/livewire/student/quiz-runner.blade.php'));

        $this->assertStringNotContainsString("dispatch('quiz-speak'", $runner);
        $this->assertStringNotContainsString("Storage::disk('public')->exists", $blade);
        $this->assertStringNotContainsString('->lastModified(', $blade);
        $this->assertStringContainsString('speakFast', $blade);
    }
}
