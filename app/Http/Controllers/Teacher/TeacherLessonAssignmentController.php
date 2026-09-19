<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\InteractiveLesson;
use App\Models\Student;
use App\Models\TeacherLessonAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherLessonAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher() === true, 403);

        $lessons = InteractiveLesson::query()
            ->withoutTenantScope()
            ->where('status', 'published')
            ->where(function ($query) use ($teacher): void {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $teacher->tenant_id);
            })
            ->orderBy('grade_level')
            ->orderBy('title')
            ->get();

        $students = Student::query()
            ->whereHas('user', function ($query) use ($teacher): void {
                $query->withoutGlobalScope('tenant')
                    ->where('tenant_id', $teacher->tenant_id);
            })
            ->orderBy('name')
            ->get();

        $assignments = TeacherLessonAssignment::query()
            ->where('tenant_id', $teacher->tenant_id)
            ->where('teacher_id', $teacher->id)
            ->with(['interactiveLesson', 'student'])
            ->latest('assigned_at')
            ->get();

        return view('teacher.lessons.index', [
            'teacher' => $teacher,
            'lessons' => $lessons,
            'students' => $students,
            'assignments' => $assignments,
        ]);
    }

    public function assign(Request $request): RedirectResponse|JsonResponse
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher() === true, 403);

        $validated = $request->validate([
            'interactive_lesson_id' => ['required', 'integer', 'exists:interactive_lessons,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['student_id'])) {
            $student = Student::query()
                ->with('user')
                ->findOrFail($validated['student_id']);

            abort_unless((int) $student->user?->tenant_id === (int) $teacher->tenant_id, 403);
        }

        $assignment = TeacherLessonAssignment::query()->updateOrCreate(
            [
                'tenant_id' => $teacher->tenant_id,
                'teacher_id' => $teacher->id,
                'interactive_lesson_id' => (int) $validated['interactive_lesson_id'],
                'student_id' => ! empty($validated['student_id']) ? (int) $validated['student_id'] : null,
            ],
            [
                'assigned_at' => now(),
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم تعيين الدرس بنجاح',
                'assignment' => $assignment,
            ], 201);
        }

        return back()->with('status', 'تم تعيين الدرس بنجاح.');
    }

    public function unassign(Request $request): RedirectResponse|JsonResponse
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher() === true, 403);

        if ($request->filled('assignment_id')) {
            $assignment = TeacherLessonAssignment::query()
                ->where('tenant_id', $teacher->tenant_id)
                ->where('teacher_id', $teacher->id)
                ->findOrFail($request->integer('assignment_id'));

            $assignment->delete();
        } else {
            $validated = $request->validate([
                'interactive_lesson_id' => ['required', 'integer'],
                'student_id' => ['nullable', 'integer'],
            ]);

            $query = TeacherLessonAssignment::query()
                ->where('tenant_id', $teacher->tenant_id)
                ->where('teacher_id', $teacher->id)
                ->where('interactive_lesson_id', (int) $validated['interactive_lesson_id']);

            if (! empty($validated['student_id'])) {
                $query->where('student_id', (int) $validated['student_id']);
            } else {
                $query->whereNull('student_id');
            }

            $query->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم إلغاء تعيين الدرس بنجاح',
            ]);
        }

        return back()->with('status', 'تم إلغاء تعيين الدرس بنجاح.');
    }
}
