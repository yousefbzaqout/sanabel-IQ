<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MaterialChunk;
use App\Models\ParentMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialChunk>
 */
class MaterialChunkFactory extends Factory
{
    public function definition(): array
    {
        $embedding = array_fill(0, 768, 0.0);
        $embedding[0] = 1.0;

        return [
            'parent_material_id' => ParentMaterial::factory(),
            'content' => fake()->paragraph(),
            'embedding' => $embedding,
            'chunk_index' => fake()->numberBetween(0, 20),
        ];
    }
}
