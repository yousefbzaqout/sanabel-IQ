<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LearningMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subject_id',
    'title',
    'description',
    'xp_reward',
    'order_column',
    'is_published',
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
}
