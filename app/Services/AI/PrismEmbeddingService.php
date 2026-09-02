<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AIServiceInterface;
use Prism\Prism\Facades\Prism;

class PrismEmbeddingService implements AIServiceInterface
{
    /**
     * @return list<float>
     */
    public function generateEmbedding(string $text): array
    {
        $provider = (string) config('services.embedding.provider', 'openrouter');
        $model = (string) config('services.embedding.model', 'google/text-embedding-004');

        $response = Prism::embeddings()
            ->using($provider, $model)
            ->fromInput($text)
            ->asEmbeddings();

        return array_map(
            static fn (int|float $value): float => (float) $value,
            $response->embeddings[0]->embedding,
        );
    }
}
