<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ParentGoalStatus;
use Database\Factories\ParentLearningGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'parent_id',
    'student_id',
    'subject_id',
    'target_activity_count',
    'target_xp',
    'start_date',
    'end_date',
    'status',
])]
class ParentLearningGoal extends Model
{
    /** @use HasFactory<ParentLearningGoalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_activity_count' => 'integer',
            'target_xp' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ParentGoalStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
