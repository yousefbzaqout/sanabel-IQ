<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TeacherAuthController extends Controller
{
    public function create(): View
    {
        return view('teacher.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if ($user === null || ! $user->isTeacher()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'هذا الحساب ليس حساب معلّم. استخدم بوابة المعلم بحساب المعلّم فقط.',
            ]);
        }

        return redirect()->intended(route('teacher.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }
}
