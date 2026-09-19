<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'student_id',
    'lesson_key',
    'interactive_lesson_id',
    'learning_material_id',
    'station',
    'concept_key',
    'event_type',
    'error_count',
    'payload',
])]
class LessonAnalytic extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'station' => 'integer',
            'error_count' => 'integer',
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<InteractiveLesson, $this> */
    public function interactiveLesson(): BelongsTo
    {
        return $this->belongsTo(InteractiveLesson::class);
    }

    /** @return BelongsTo<LearningMaterial, $this> */
    public function learningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class);
    }
}
