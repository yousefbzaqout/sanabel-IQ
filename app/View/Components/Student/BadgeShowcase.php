<?php

declare(strict_types=1);

namespace App\View\Components\Student;

use App\Models\Badge;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class BadgeShowcase extends Component
{
    /**
     * @param  Collection<int, Badge>|list<Badge>  $badges
     */
    public function __construct(
        public Collection|array $badges = [],
    ) {
        $this->badges = $badges instanceof Collection
            ? $badges
            : collect($badges);
    }

    public function render(): View
    {
        return view('components.student.badge-showcase');
    }
}
