<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Lessons\InteractiveLessonCatalog;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicInteractiveLessonPreviewController extends Controller
{
    public function show(string $lessonKey): View
    {
        if (! InteractiveLessonCatalog::exists($lessonKey)) {
            throw new NotFoundHttpException('Interactive lesson not found.');
        }

        try {
            $lesson = InteractiveLessonCatalog::get($lessonKey);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException('Interactive lesson not found.');
        }

        if (($lesson['source'] ?? '') === 'database' && ($lesson['status'] ?? '') !== 'published') {
            throw new NotFoundHttpException('Interactive lesson not published.');
        }

        return view('preview.interactive-lesson', [
            'lessonKey' => (string) ($lesson['lesson_key'] ?? $lessonKey),
            'lessonTitle' => (string) ($lesson['title'] ?? 'معاينة درس تفاعلي'),
        ]);
    }
}
