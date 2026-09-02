<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\GenerateStudyRecommendationsJob;
use App\Models\Student;
use App\Models\StudyRecommendation;
use App\Services\Analytics\SubjectAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ParentAnalyticsController extends Controller
{
    private const RECOMMENDATION_COOLDOWN_MINUTES = 5;

    public function index(Request $request, SubjectAnalyticsService $subjectAnalytics): View
    {
        $student = $this->resolveActiveStudent($request);
        $this->authorize('view', $student);

        $analysis = $subjectAnalytics->analyze($student);
        $weakTopics = $subjectAnalytics->identifyWeaknesses($student);

        $latestRecommendation = StudyRecommendation::query()
            ->where('student_id', $student->id)
            ->latest('generated_at')
            ->first();

        return view('parent.analytics.index', [
            'student' => $student,
            'analysis' => $analysis,
            'weakTopics' => $weakTopics,
            'latestRecommendation' => $latestRecommendation,
            'chartLabels' => collect($analysis['subject_breakdown'])->pluck('subject')->all(),
            'chartValues' => collect($analysis['subject_breakdown'])->pluck('accuracy_percent')->all(),
        ]);
    }

    public function generateRecommendations(Request $request): RedirectResponse
    {
        $student = $this->resolveActiveStudent($request);
        $this->authorize('view', $student);

        if ($this->hasRecentRecommendation($student)) {
            return redirect()
                ->route('parent.analytics')
                ->with('error', __('A study recommendation was generated recently. Please wait :minutes minutes before requesting another.', [
                    'minutes' => self::RECOMMENDATION_COOLDOWN_MINUTES,
                ]));
        }

        try {
            GenerateStudyRecommendationsJob::dispatchSync($student->id);
        } catch (Throwable) {
            return redirect()
                ->route('parent.analytics')
                ->with('error', __('Unable to generate study recommendations right now. Please try again in a few minutes.'));
        }

        return redirect()
            ->route('parent.analytics')
            ->with('status', __('Smart study recommendations have been generated for your child.'));
    }

    private function resolveActiveStudent(Request $request): Student
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');
        $student = $request->user()?->students()->find($activeStudentId);

        abort_if($student === null, 403);

        return $student;
    }

    private function hasRecentRecommendation(Student $student): bool
    {
        return StudyRecommendation::query()
            ->where('student_id', $student->id)
            ->where('generated_at', '>=', now()->subMinutes(self::RECOMMENDATION_COOLDOWN_MINUTES))
            ->exists();
    }
}
