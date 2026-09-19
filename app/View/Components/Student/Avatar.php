<?php

declare(strict_types=1);

namespace App\View\Components\Student;

use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

class Avatar extends Component
{
    public string $initial;

    public string $backgroundColor;

    public ?string $imageUrl;

    public string $sizeClass;

    public function __construct(
        public Student $student,
        public string $size = 'md',
    ) {
        $name = trim((string) $student->name);
        $this->initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';
        $this->backgroundColor = $this->resolveBackgroundColor($name);
        $this->imageUrl = filled($student->avatar_path)
            ? Storage::disk('public')->url((string) $student->avatar_path)
            : null;
        $this->sizeClass = match ($size) {
            'sm' => 'h-8 w-8 text-sm',
            'lg' => 'h-16 w-16 text-2xl',
            default => 'h-12 w-12 text-lg',
        };
    }

    public function render(): View
    {
        return view('components.student.avatar');
    }

    private function resolveBackgroundColor(string $name): string
    {
        $palette = [
            '#0f766e', // teal-700
            '#1d4ed8', // blue-700
            '#b45309', // amber-700
            '#be123c', // rose-700
            '#6d28d9', // violet-700
            '#047857', // emerald-700
            '#c2410c', // orange-700
            '#0369a1', // sky-700
        ];

        $index = abs(crc32($name)) % count($palette);

        return $palette[$index];
    }
}
