<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Lessons\InteractiveStationConfigRepository;

final class MascotAiFeedbackService
{
    public function __construct(
        private readonly InteractiveStationConfigRepository $stations = new InteractiveStationConfigRepository,
    ) {}

    /**
     * @param  array{
     *     attempts?: int,
     *     time_spent?: int|float,
     *     mastery_score?: int|float,
     *     station?: int,
     *     lesson_key?: string
     * }  $params
     * @return array{
     *     tone: string,
     *     mascot_state: string,
     *     message: string,
     *     audio_prompt: string,
     *     station_hint: string
     * }
     */
    public function generate(array $params): array
    {
        $attempts = max(0, (int) ($params['attempts'] ?? 0));
        $timeSpent = max(0, (int) round((float) ($params['time_spent'] ?? 0)));
        $mastery = (int) max(0, min(100, round((float) ($params['mastery_score'] ?? 0))));
        $station = max(1, min(6, (int) ($params['station'] ?? 1)));
        $lessonKey = (string) ($params['lesson_key'] ?? '');

        $tone = $this->resolveTone($attempts, $timeSpent, $mastery);
        $stationHint = $this->stationIntro($station, $lessonKey);
        $message = $this->messageForTone($tone, $station, $lessonKey);

        return [
            'tone' => $tone,
            'mascot_state' => match ($tone) {
                'encourage' => 'encouraging',
                'coach' => 'thinking',
                default => 'happy',
            },
            'message' => $message,
            'audio_prompt' => $message,
            'station_hint' => $stationHint,
        ];
    }

    /**
     * @return array{tone: string, mascot_state: string, message: string, audio_prompt: string, station_hint: string}
     */
    public function forStation(int $station, string $lessonKey = ''): array
    {
        $station = max(1, min(6, $station));
        $message = $this->stationIntro($station, $lessonKey);

        return [
            'tone' => 'intro',
            'mascot_state' => match ($station) {
                4 => 'thinking',
                5 => 'encouraging',
                default => 'happy',
            },
            'message' => $message,
            'audio_prompt' => $message,
            'station_hint' => $message,
        ];
    }

    private function resolveTone(int $attempts, int $timeSpent, int $mastery): string
    {
        if ($mastery >= 85 && $attempts <= 2 && $timeSpent <= 12) {
            return 'celebrate';
        }

        if ($mastery >= 80 && $attempts >= 4) {
            return 'persist';
        }

        if ($mastery < 50 || ($attempts >= 3 && $mastery < 70)) {
            return 'encourage';
        }

        return 'coach';
    }

    private function messageForTone(string $tone, int $station, string $lessonKey): string
    {
        return match ($tone) {
            'celebrate' => 'يا بطل! سرعة ودقة رائعة — سنبل فخور بك!',
            'persist' => 'أحسنت على المثابرة! صبرت ونجحت، هذا عمل الأبطال.',
            'encourage' => 'لا بأس، حاول مرة أخرى بهدوء… أنت قريب جداً!',
            default => $this->stationCoachTip($station, $lessonKey),
        };
    }

    private function stationIntro(int $station, string $lessonKey = ''): string
    {
        if ($lessonKey !== '') {
            $prompt = $this->stations->sonbolPrompt($lessonKey, $station);
            if (filled($prompt)) {
                return (string) $prompt;
            }
        }

        return match ($station) {
            1 => 'هيا نسمع الحركات الثلاث معاً: رَ، رُ، رِ!',
            2 => 'جاهز لفرقعة الفقاعات؟ رتّب الأصوات لتكوّن رَمَل!',
            3 => 'أين يختبئ حرف الراء؟ في البداية، الوسط، أو النهاية؟',
            4 => 'ارسم حرف الراء بإصبعك من الأعلى إلى الأسفل.',
            5 => 'امسح الرمل واكتشف بيارة الرمان معي!',
            6 => 'شاهد كيف يحلّ سنبل السؤال، ثم انطلق للاختبار!',
            default => 'هيا نتعلم مع سنبل!',
        };
    }

    private function stationCoachTip(int $station, string $lessonKey = ''): string
    {
        if ($lessonKey !== '') {
            $prompt = $this->stations->sonbolPrompt($lessonKey, $station);
            if (filled($prompt)) {
                return (string) $prompt;
            }
        }

        return match ($station) {
            1 => 'ركّز على صوت الحركة جيداً قبل أن تنتقل.',
            2 => 'تذكر الترتيب: رَ ثم مَ ثم لْ.',
            3 => 'ابحث عن حرف الراء المضيء في الكلمة.',
            4 => 'ابدأ من أعلى القوس وانزل بهدوء.',
            5 => 'امسح طبقة الرمل ببطء لترى المفاجأة.',
            6 => 'استمع للشرح، ثم اضغط زر الاختبار عندما تكون جاهزاً.',
            default => 'خذ نفساً عميقاً وتابع يا بطل.',
        };
    }
}
