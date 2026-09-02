<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class ParentCurriculumController extends Controller
{
    public function materials(Subject $subject): JsonResponse
    {
        $materials = $subject->publishedLearningMaterials()
            ->get(['id', 'subject_id', 'title', 'description', 'xp_reward', 'order_column', 'is_published']);

        return response()->json([
            'data' => $materials,
        ]);
    }
}
