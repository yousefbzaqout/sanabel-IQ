<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Student\ChildLoginCredentialService;
use App\Support\AuthRedirectResolver;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChildPinLoginController extends Controller
{
    public function lookup(Request $request, ChildLoginCredentialService $credentials): JsonResponse
    {
        $validated = $request->validate([
            'family_code' => ['required', 'string', 'size:6'],
        ]);

        $children = $credentials->childrenForFamilyCode($validated['family_code']);

        if ($children === []) {
            throw ValidationException::withMessages([
                'family_code' => 'رمز العائلة غير صحيح أو لا توجد أبناء مرتبطون به.',
            ]);
        }

        return response()->json([
            'family_code' => strtoupper($validated['family_code']),
            'children' => $children,
        ]);
    }

    public function store(Request $request, ChildLoginCredentialService $credentials): RedirectResponse
    {
        $validated = $request->validate([
            'family_code' => ['required', 'string', 'size:6'],
            'student_id' => ['required', 'integer'],
            'pin' => ['required', 'string', 'regex:/^\d{4}$/'],
        ], [
            'pin.regex' => 'رمز الدخول يجب أن يكون 4 أرقام.',
        ]);

        $this->ensureIsNotRateLimited($request, $validated['family_code']);

        $student = $credentials->findChildForLogin(
            $validated['family_code'],
            (int) $validated['student_id'],
        );

        if ($student === null || ! $credentials->verifyPin($student, $validated['pin'])) {
            RateLimiter::hit($this->throttleKey($request, $validated['family_code']));

            throw ValidationException::withMessages([
                'pin' => 'رمز الدخول غير صحيح. حاول مرة أخرى.',
            ]);
        }

        $loginUser = $student->loginUser;
        if ($loginUser === null) {
            RateLimiter::hit($this->throttleKey($request, $validated['family_code']));

            throw ValidationException::withMessages([
                'pin' => 'حساب دخول الطالب غير جاهز. اطلب من ولي الأمر إعادة تعيين الرمز.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $validated['family_code']));

        Auth::login($loginUser, false);
        $request->session()->regenerate();
        $request->session()->put('active_student_id', $student->id);

        return redirect(app(AuthRedirectResolver::class)->redirectPath($loginUser, $request));
    }

    private function ensureIsNotRateLimited(Request $request, string $familyCode): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $familyCode), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $familyCode));

        throw ValidationException::withMessages([
            'pin' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(Request $request, string $familyCode): string
    {
        return Str::transliterate(Str::lower($familyCode).'|child-pin|'.$request->ip());
    }
}
