<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StudentQuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id',
    'learning_material_id',
    'total_questions',
    'correct_answers',
    'score_percentage',
    'xp_earned',
    'completed_at',
])]
class StudentQuizAttempt extends Model
{
    /** @use HasFactory<StudentQuizAttemptFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'score_percentage' => 'decimal:2',
            'xp_earned' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<LearningMaterial, $this> */
    public function learningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class);
    }

    /** @return HasMany<StudentQuizAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(StudentQuizAnswer::class, 'attempt_id');
    }
}
