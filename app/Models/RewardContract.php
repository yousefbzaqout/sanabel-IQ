<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RewardContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'title', 'description', 'xp_cost', 'is_fulfilled', 'fulfilled_at'])]
class RewardContract extends Model
{
    /** @use HasFactory<RewardContractFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'xp_cost' => 'integer',
            'is_fulfilled' => 'boolean',
            'fulfilled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
