<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

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
}
