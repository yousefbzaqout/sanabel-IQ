<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the caller is authenticated and has a valid owned active student in session.
 * Used for AI scoring / lesson telemetry endpoints.
 */
class EnsureActiveStudentAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $activeStudentId = (int) $request->session()->get('active_student_id');

        if ($activeStudentId <= 0) {
            abort(Response::HTTP_FORBIDDEN, 'Active student context is required.');
        }

        $student = Student::query()
            ->whereKey($activeStudentId)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('login_user_id', $user->id);
            })
            ->first();

        if ($student === null) {
            abort(Response::HTTP_FORBIDDEN, 'Active student context is required.');
        }

        $request->attributes->set('activeStudent', $student);

        return $next($request);
    }
}
