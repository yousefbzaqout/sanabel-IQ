<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StudyRecommendationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'weak_topics_json',
    'actionable_tips_json',
    'suggested_focus_area',
    'generated_at',
])]
class StudyRecommendation extends Model
{
    /** @use HasFactory<StudyRecommendationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weak_topics_json' => 'array',
            'actionable_tips_json' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
