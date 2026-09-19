<?php

use App\Http\Middleware\EnsureActiveChildContext;
use App\Http\Middleware\EnsureActiveStudentAccess;
use App\Http\Middleware\EnsureStudentArabicLocale;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsTeacher;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.child' => EnsureActiveChildContext::class,
            'active.student' => EnsureActiveStudentAccess::class,
            'admin' => EnsureUserIsAdmin::class,
            'teacher' => EnsureUserIsTeacher::class,
            'student.locale' => EnsureStudentArabicLocale::class,
            'tenant' => IdentifyTenant::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('teacher') || $request->is('teacher/*')) {
                return route('teacher.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->expectsJson() || $request->is('api/*'),
        );

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*') && ! $request->is('student/ai/*')) {
                return null;
            }

            return response()->json([
                'message' => 'لقد تجاوزت الحد المسموح للطلبات. حاول مرة أخرى بعد قليل.',
                'error' => 'too_many_requests',
            ], 429);
        });
    })->create();
