<?php

declare(strict_types=1);

namespace App\View\Components\Student;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InteractiveLesson extends Component
{
    /**
     * @param  array<string, mixed>  $lesson
     */
    public function __construct(
        public array $lesson,
        public int $currentState = 1,
        public string $quizUrl = '#',
        public string $mascotState = 'happy',
    ) {}

    public function render(): View
    {
        return view('components.student.interactive-lesson');
    }
}
