<?php

declare(strict_types=1);

namespace App\View\Components\Student;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Mascot extends Component
{
    /**
     * @var list<string>
     */
    public const STATES = ['idle', 'thinking', 'happy', 'encouraging'];

    public string $state;

    public function __construct(
        string $state = 'idle',
        public string $message = 'مرحباً! أنا سنبل، هيا نتعلم معاً!',
        public bool $showBubble = true,
        public string $size = 'md',
    ) {
        $this->state = in_array($state, self::STATES, true) ? $state : 'idle';
    }

    public function render(): View
    {
        return view('components.student.mascot');
    }
}
