<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChildOnboardingController extends Controller
{
    public function show(): View
    {
        $this->authorize('create', Student::class);

        return view('onboarding.child');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $student = $request->user()->students()->create($request->validated());

        $request->session()->put('active_student_id', $student->id);

        return redirect()->route('dashboard');
    }
}
