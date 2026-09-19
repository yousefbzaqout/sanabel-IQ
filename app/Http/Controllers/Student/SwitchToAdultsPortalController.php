<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SwitchToAdultsPortalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->isParent()) {
            return redirect('/parent');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Do not set Laravel's url.intended — Filament /admin/login also
        // consumes it and would bounce school admins to /parent (403).
        // Parent email login already resolves home to /parent.

        return redirect()
            ->route('login', ['role' => 'parent'])
            ->with('status', 'سجّل دخول ولي الأمر للمتابعة إلى بوابة الكبار.');
    }
}
