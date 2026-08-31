<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentBadge extends Pivot
{
    protected $table = 'student_badge';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }
}
