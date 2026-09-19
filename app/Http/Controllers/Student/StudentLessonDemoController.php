<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentLessonDemoController extends Controller
{
    public function letterRaa(Request $request): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->findAccessibleStudentOrFail($activeStudentId);

        return view('student.lesson-demo.letter-raa', [
            'student' => $student,
        ]);
    }
}
