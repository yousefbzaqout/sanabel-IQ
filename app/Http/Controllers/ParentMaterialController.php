<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MaterialStatus;
use App\Http\Requests\StoreParentMaterialRequest;
use App\Jobs\ProcessPDFMaterialJob;
use App\Models\ParentMaterial;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ParentMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ParentMaterial::class);

        $activeStudentId = (int) $request->session()->get('active_student_id');

        $materials = $request->user()
            ->parentMaterials()
            ->where('student_id', $activeStudentId)
            ->latest()
            ->get();

        return view('dashboard', [
            'materials' => $materials,
        ]);
    }

    public function show(ParentMaterial $parentMaterial): View
    {
        $this->authorize('view', $parentMaterial);

        return view('materials.show', [
            'material' => $parentMaterial,
        ]);
    }

    public function store(StoreParentMaterialRequest $request): RedirectResponse
    {
        $this->authorize('create', ParentMaterial::class);

        $activeStudentId = (int) $request->session()->get('active_student_id');
        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        $uploadedFile = $request->file('file');
        $hashedFilename = hash('sha256', $uploadedFile->getClientOriginalName().microtime(true)).'.pdf';
        $filePath = $uploadedFile->storeAs('', $hashedFilename, 'materials');

        $material = $request->user()->parentMaterials()->create([
            'student_id' => $student->id,
            'title' => $request->validated('title'),
            'file_path' => $filePath,
            'type' => $request->validated('type'),
            'status' => MaterialStatus::Pending,
        ]);

        ProcessPDFMaterialJob::dispatch($material);

        return redirect()
            ->route('dashboard')
            ->with('status', __('Material uploaded successfully.'));
    }

    public function destroy(Request $request, ParentMaterial $parentMaterial): RedirectResponse
    {
        $this->authorize('delete', $parentMaterial);

        Storage::disk('materials')->delete($parentMaterial->file_path);
        $parentMaterial->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', __('Material deleted.'));
    }
}
