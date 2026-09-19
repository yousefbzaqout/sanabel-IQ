<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Audio\ArabicTtsSynthesizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentTextToSpeechController extends Controller
{
    public function __invoke(Request $request): Response|StreamedResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:400'],
        ]);

        $tts = new ArabicTtsSynthesizer('local');
        $text = $tts->normalizeText($validated['text']);

        if ($text === '') {
            return response()->json(['message' => 'The text field is required.'], 422);
        }

        $cacheKey = 'tts/ar/'.hash('sha256', $text).'.mp3';
        // Live interactive TTS skips remote diacritizer — cache hits stay instant and cold starts avoid 12s HTTP stalls.
        $tts->synthesizeToPublicPath($text, $cacheKey, force: false, withDiacritics: false);

        return response(Storage::disk('local')->get($cacheKey), 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
