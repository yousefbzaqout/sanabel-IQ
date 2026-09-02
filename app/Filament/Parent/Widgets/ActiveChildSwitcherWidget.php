<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use Filament\Widgets\Widget;

class ActiveChildSwitcherWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -10;

    protected string $view = 'filament.parent.widgets.active-child-switcher';

    public ?string $selectedStudentId = null;

    public function mount(): void
    {
        $this->selectedStudentId = (string) session('active_student_id', '');
    }

    public function switchActiveChild(): void
    {
        $student = auth()->user()?->students()->find((int) $this->selectedStudentId);

        abort_if($student === null, 403);

        session(['active_student_id' => $student->id]);

        $this->dispatch('$refresh');
    }
}
