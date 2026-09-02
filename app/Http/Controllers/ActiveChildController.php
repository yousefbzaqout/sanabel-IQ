<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveChildController extends Controller
{
    public function select(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('select', $student);

        $request->session()->put('active_student_id', $student->id);

        return back()->with('status', __('Active child updated.'));
    }
}
