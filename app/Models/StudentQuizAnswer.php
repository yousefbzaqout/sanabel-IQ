<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StudentQuizAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'attempt_id',
    'question_id',
    'selected_option_id',
    'is_correct',
    'points_awarded',
])]
class StudentQuizAnswer extends Model
{
    /** @use HasFactory<StudentQuizAnswerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
        ];
    }

    /** @return BelongsTo<StudentQuizAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(StudentQuizAttempt::class, 'attempt_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<QuestionOption, $this> */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
