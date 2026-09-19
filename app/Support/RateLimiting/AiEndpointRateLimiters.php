<?php

declare(strict_types=1);

namespace App\Support\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AiEndpointRateLimiters
{
    public static function register(): void
    {
        RateLimiter::for('ai-voice-eval', function (Request $request): Limit {
            return Limit::perMinute(10)
                ->by(self::studentKey('ai-voice', $request))
                ->response(fn (): \Illuminate\Http\JsonResponse => self::tooManyResponse(
                    'لقد تجاوزت الحد المسموح لتقييم النطق. حاول بعد دقيقة.',
                ));
        });

        RateLimiter::for('ai-trace-eval', function (Request $request): Limit {
            return Limit::perMinute(15)
                ->by(self::studentKey('ai-trace', $request))
                ->response(fn (): \Illuminate\Http\JsonResponse => self::tooManyResponse(
                    'لقد تجاوزت الحد المسموح لتحليل الرسم. حاول بعد دقيقة.',
                ));
        });

        RateLimiter::for('ai-micro-hint', function (Request $request): Limit {
            return Limit::perMinute(20)
                ->by(self::studentKey('ai-hint', $request))
                ->response(fn (): \Illuminate\Http\JsonResponse => self::tooManyResponse(
                    'لقد تجاوزت الحد المسموح لتلميحات سنبل. حاول بعد دقيقة.',
                ));
        });
    }

    private static function studentKey(string $prefix, Request $request): string
    {
        $studentId = (int) $request->session()->get('active_student_id');

        if ($studentId > 0) {
            return "{$prefix}:{$studentId}";
        }

        return "{$prefix}:user:".($request->user()?->id ?? $request->ip());
    }

    private static function tooManyResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => 'too_many_requests',
        ], 429);
    }
}
