<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityStatus;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'parent_material_id', 'title', 'payload', 'xp_reward', 'status'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'xp_reward' => 'integer',
            'status' => ActivityStatus::class,
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<ParentMaterial, $this> */
    public function parentMaterial(): BelongsTo
    {
        return $this->belongsTo(ParentMaterial::class);
    }

    /** @return HasMany<ActivityAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(ActivityAttempt::class);
    }
}
