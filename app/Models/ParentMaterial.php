<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use Database\Factories\ParentMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'student_id', 'title', 'file_path', 'type', 'status'])]
class ParentMaterial extends Model
{
    /** @use HasFactory<ParentMaterialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'status' => MaterialStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return HasMany<MaterialChunk, $this> */
    public function materialChunks(): HasMany
    {
        return $this->hasMany(MaterialChunk::class);
    }
}
