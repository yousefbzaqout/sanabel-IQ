<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'interactive_lesson_id',
    'station_number',
    'station_type',
    'title',
    'instructions',
    'sonbol_prompt',
    'config',
    'assets',
    'is_skippable',
    'order_column',
])]
class InteractiveLessonStation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'station_number' => 'integer',
            'config' => 'array',
            'assets' => 'array',
            'is_skippable' => 'boolean',
            'order_column' => 'integer',
        ];
    }

    /** @return BelongsTo<InteractiveLesson, $this> */
    public function interactiveLesson(): BelongsTo
    {
        return $this->belongsTo(InteractiveLesson::class);
    }
}
