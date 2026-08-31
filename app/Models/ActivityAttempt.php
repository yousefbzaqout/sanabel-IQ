<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActivityAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'activity_id',
    'student_id',
    'score',
    'total_questions',
    'xp_earned',
    'answers_json',
    'completed_at',
])]
class ActivityAttempt extends Model
{
    /** @use HasFactory<ActivityAttemptFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total_questions' => 'integer',
            'xp_earned' => 'integer',
            'answers_json' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
