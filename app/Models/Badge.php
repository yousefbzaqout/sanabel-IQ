<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BadgeCriteriaType;
use Database\Factories\BadgeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'code',
    'name_ar',
    'description_ar',
    'icon',
    'criteria_type',
    'criteria_value',
])]
class Badge extends Model
{
    /** @use HasFactory<BadgeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criteria_type' => BadgeCriteriaType::class,
            'criteria_value' => 'integer',
        ];
    }

    /** @return BelongsToMany<Student, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_badges')
            ->using(StudentBadge::class)
            ->withPivot(['unlocked_at'])
            ->withTimestamps();
    }
}
