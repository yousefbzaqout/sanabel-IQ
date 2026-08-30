<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasVectorSearch
{
    /**
     * @param  Builder<static>  $query
     * @param  list<int|float>  $embedding
     * @return Builder<static>
     */
    public function scopeNearestNeighbors(Builder $query, array $embedding, int $limit = 5): Builder
    {
        $vector = '['.implode(',', array_map(
            static fn (int|float $value): string => (string) $value,
            $embedding,
        )).']';

        return $query
            ->orderByRaw('embedding <=> ?::vector', [$vector])
            ->limit($limit);
    }
}
