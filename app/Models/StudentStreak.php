<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StudentStreakFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'current_streak',
    'max_streak',
    'last_activity_date',
])]
class StudentStreak extends Model
{
    /** @use HasFactory<StudentStreakFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_streak' => 'integer',
            'max_streak' => 'integer',
            'last_activity_date' => 'date',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
