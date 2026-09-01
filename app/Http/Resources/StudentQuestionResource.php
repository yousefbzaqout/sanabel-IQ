<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Question */
class StudentQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'prompt' => $this->prompt,
            'points' => $this->points,
            'order_column' => $this->order_column,
            'options' => $this->options->map(fn ($option): array => [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'order_column' => $option->order_column,
            ])->values()->all(),
        ];
    }
}
