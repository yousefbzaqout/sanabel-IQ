<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Services\Tenancy\TenantSeatLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildOnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Student::class);

        if ($request->user()->students()->exists()) {
            return redirect()
                ->route('students.create')
                ->with('status', __('You already have children on your account. Add another child below.'));
        }

        return view('onboarding.child');
    }

    public function store(StoreStudentRequest $request, TenantSeatLimitService $seatLimitService): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $user = $request->user();
        if ($user?->tenant_id !== null) {
            $tenant = Tenant::query()->find($user->tenant_id);
            if ($tenant !== null) {
                $seatLimitService->ensureCanAddStudent($tenant);
            }
        }

        $student = $user->students()->create($request->validated());

        $request->session()->put('active_student_id', $student->id);

        return redirect()->route('dashboard');
    }
}
