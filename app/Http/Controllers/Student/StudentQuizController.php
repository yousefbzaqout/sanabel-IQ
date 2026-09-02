<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitQuizAnswersRequest;
use App\Http\Resources\StudentQuestionResource;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Services\Gameplay\QuizScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    public function show(Request $request, LearningMaterial $learningMaterial): View|JsonResponse
    {
        $material = LearningMaterial::query()
            ->published()
            ->whereKey($learningMaterial->id)
            ->with(['questions.options'])
            ->firstOrFail();

        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        if ($request->expectsJson()) {
            return response()->json([
                'material' => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'xp_reward' => $material->xp_reward,
                ],
                'questions' => StudentQuestionResource::collection($material->questions)->resolve(),
            ]);
        }

        return view('student.quiz.show', [
            'material' => $material,
            'student' => $student,
            'questions' => StudentQuestionResource::collection($material->questions)->resolve(),
        ]);
    }

    public function completion(Request $request, LearningMaterial $learningMaterial): View
    {
        $material = LearningMaterial::query()
            ->published()
            ->whereKey($learningMaterial->id)
            ->firstOrFail();

        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        return view('student.quiz.completion', [
            'material' => $material,
            'student' => $student,
        ]);
    }

    public function submit(
        SubmitQuizAnswersRequest $request,
        LearningMaterial $learningMaterial,
        QuizScoringService $scoringService,
    ): JsonResponse {
        $material = LearningMaterial::query()
            ->published()
            ->whereKey($learningMaterial->id)
            ->with(['questions.options'])
            ->firstOrFail();

        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        /** @var list<array{question_id: int, selected_option_id: int}> $answers */
        $answers = array_values($request->validated('answers'));

        $result = $scoringService->submit($material, $student, $answers);

        return response()->json([
            'score' => $result['score'],
            'total_questions' => $result['total_questions'],
            'percentage' => $result['percentage'],
            'score_percentage' => $result['score_percentage'],
            'xp_earned' => $result['xp_earned'],
            'total_xp' => $student->fresh()->total_xp,
            'feedback' => $result['feedback'],
            'attempt_id' => $result['attempt']->id,
        ]);
    }
}
