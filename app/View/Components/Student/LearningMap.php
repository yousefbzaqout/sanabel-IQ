<?php

declare(strict_types=1);

namespace App\View\Components\Student;

use App\Models\Student;
use App\Services\Student\LearningMapService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LearningMap extends Component
{
    /**
     * @var list<array{
     *     id: int,
     *     title: string,
     *     subject_id: int,
     *     material_id: int|null,
     *     state: 'completed'|'available'|'locked',
     *     url: string|null,
     *     icon: string|null,
     *     position: int
     * }>
     */
    public array $mapNodes;

    public function __construct(
        public Student $student,
    ) {
        $this->mapNodes = app(LearningMapService::class)->buildNodes($student);
    }

    /**
     * @return list<array{
     *     id: int,
     *     title: string,
     *     subject_id: int,
     *     material_id: int|null,
     *     state: 'completed'|'available'|'locked',
     *     url: string|null,
     *     icon: string|null,
     *     position: int
     * }>
     */
    public function nodes(): array
    {
        return $this->mapNodes;
    }

    public function render(): View
    {
        return view('components.student.learning-map');
    }
}
