<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveChildContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->canAccessAdminPanel() || $user->isTeacher()) {
            return $next($request);
        }

        if ($user->isStudent()) {
            $profile = $user->learningProfile;

            if ($profile === null) {
                abort(Response::HTTP_FORBIDDEN, 'Student learning profile is required.');
            }

            $request->session()->put('active_student_id', $profile->id);
            View::share('activeStudent', $profile);
            View::share('parentStudents', collect([$profile]));

            $request->attributes->set('activeStudent', $profile);

            return $next($request);
        }

        $students = $user->students()->orderBy('id')->get();

        if ($students->isEmpty()) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, 'Active student context is required.');
            }

            return redirect()->route('onboarding.child');
        }

        $activeId = (int) $request->session()->get('active_student_id');
        $activeStudent = $students->first(
            fn (Student $student): bool => $student->id === $activeId,
        );

        if ($activeStudent === null) {
            $activeStudent = $students->first();
            $request->session()->put('active_student_id', $activeStudent->id);
        }

        View::share('activeStudent', $activeStudent);
        View::share('parentStudents', $students);
        $request->attributes->set('activeStudent', $activeStudent);

        return $next($request);
    }
}
