<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'learning_material_id',
    'lesson_key',
    'title',
    'subtitle',
    'subject_code',
    'grade_level',
    'station_count',
    'status',
    'intro_audio_path',
    'meta',
])]
class InteractiveLesson extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'station_count' => 'integer',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<LearningMaterial, $this> */
    public function learningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class);
    }

    /** @return HasMany<InteractiveLessonStation, $this> */
    public function stations(): HasMany
    {
        return $this->hasMany(InteractiveLessonStation::class)->orderBy('order_column');
    }

    /** @return HasMany<LessonAnalytic, $this> */
    public function analytics(): HasMany
    {
        return $this->hasMany(LessonAnalytic::class);
    }
}
