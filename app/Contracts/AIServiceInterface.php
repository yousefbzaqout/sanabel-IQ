<?php

declare(strict_types=1);

namespace App\Contracts;

interface AIServiceInterface
{
    /**
     * @return list<float>
     */
    public function generateEmbedding(string $text): array;
}
