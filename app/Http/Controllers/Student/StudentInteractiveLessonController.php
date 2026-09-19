<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InteractiveLesson;
use App\Models\Student;
use App\Support\Lessons\InteractiveLessonCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StudentInteractiveLessonController extends Controller
{
    public function show(Request $request, string $lessonKey): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->findAccessibleStudentOrFail($activeStudentId);

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

        return view('student.interactive-lesson.show', [
            'student' => $student,
            'lessonKey' => (string) ($lesson['lesson_key'] ?? $lessonKey),
            'lessonTitle' => (string) ($lesson['title'] ?? 'درس تفاعلي'),
        ]);
    }

    public function showById(Request $request, int $interactiveLesson): RedirectResponse
    {
        $lesson = InteractiveLesson::query()
            ->withoutTenantScope()
            ->whereKey($interactiveLesson)
            ->firstOrFail();

        if ($lesson->status !== 'published') {
            throw new NotFoundHttpException('Interactive lesson not published.');
        }

        return redirect()->route('student.interactive-lesson.show', [
            'lessonKey' => $lesson->lesson_key,
        ]);
    }
}
