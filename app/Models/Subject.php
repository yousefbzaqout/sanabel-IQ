<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'code', 'grade_level', 'icon', 'description'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
        ];
    }

    /** @return HasMany<ParentMaterial, $this> */
    public function parentMaterials(): HasMany
    {
        return $this->hasMany(ParentMaterial::class);
    }

    /** @return HasMany<ParentLearningGoal, $this> */
    public function parentLearningGoals(): HasMany
    {
        return $this->hasMany(ParentLearningGoal::class);
    }

    /** @return HasMany<LearningMaterial, $this> */
    public function learningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class)->orderBy('order_column');
    }
}
