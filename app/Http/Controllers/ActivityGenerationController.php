<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MaterialStatus;
use App\Jobs\GenerateActivityFromMaterialJob;
use App\Models\ParentMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityGenerationController extends Controller
{
    public function store(Request $request, ParentMaterial $parentMaterial): RedirectResponse
    {
        $this->authorize('generate', $parentMaterial);

        $activeStudentId = (int) $request->session()->get('active_student_id');

        abort_unless($parentMaterial->student_id === $activeStudentId, 403);

        abort_unless($parentMaterial->status === MaterialStatus::Completed, 422);

        GenerateActivityFromMaterialJob::dispatch($parentMaterial);

        return redirect()
            ->route('dashboard')
            ->with('status', __('Educational activity generation started.'));
    }
}
