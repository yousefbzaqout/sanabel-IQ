<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'name', 'grade_level', 'school_term', 'total_xp', 'coins', 'lives', 'avatar_path'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'school_term' => 'integer',
            'total_xp' => 'integer',
            'coins' => 'integer',
            'lives' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return HasMany<RewardContract, $this> */
    public function rewardContracts(): HasMany
    {
        return $this->hasMany(RewardContract::class);
    }

    /** @return HasMany<ParentMaterial, $this> */
    public function parentMaterials(): HasMany
    {
        return $this->hasMany(ParentMaterial::class);
    }

    public function addXp(int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->increment('total_xp', $amount);
    }

    /** @return HasMany<ActivityAttempt, $this> */
    public function activityAttempts(): HasMany
    {
        return $this->hasMany(ActivityAttempt::class);
    }

    /** @return HasMany<StudentQuizAttempt, $this> */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(StudentQuizAttempt::class);
    }

    /** @return HasMany<StudyRecommendation, $this> */
    public function studyRecommendations(): HasMany
    {
        return $this->hasMany(StudyRecommendation::class);
    }

    /** @return HasMany<ParentLearningGoal, $this> */
    public function parentLearningGoals(): HasMany
    {
        return $this->hasMany(ParentLearningGoal::class);
    }

    /** @return BelongsToMany<Badge, $this> */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'student_badge')
            ->using(StudentBadge::class)
            ->withPivot(['unlocked_at'])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::deleting(function (Student $student): void {
            $student->parentMaterials()->each(function (ParentMaterial $material): void {
                Storage::disk('materials')->delete($material->file_path);
                $material->delete();
            });
        });
    }
}
