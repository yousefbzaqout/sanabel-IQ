<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Services\Tenancy\TenantSeatLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $students = $request->user()->students()->latest()->get();

        return view('students.index', [
            'students' => $students,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('students.create');
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

        return redirect()->route('students.show', $student);
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        return view('students.show', [
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $student->update($request->validated());

        return redirect()->route('students.show', $student);
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->delete();

        return redirect()->route('students.index');
    }
}
