<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LearningMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'subject_id',
    'title',
    'description',
    'xp_reward',
    'order_column',
    'is_published',
    'audio_path',
])]
class LearningMaterial extends Model
{
    /** @use HasFactory<LearningMaterialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'xp_reward' => 'integer',
            'order_column' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_column')->orderBy('id');
    }

    /** @return HasOne<InteractiveLesson, $this> */
    public function interactiveLesson(): HasOne
    {
        return $this->hasOne(InteractiveLesson::class);
    }

    public function studentLaunchUrl(): string
    {
        $interactive = $this->relationLoaded('interactiveLesson')
            ? $this->interactiveLesson
            : $this->interactiveLesson()->first();

        if ($interactive !== null && $interactive->status === 'published') {
            return route('student.interactive-lesson.show', $interactive->lesson_key);
        }

        return route('student.materials.quiz', $this);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
