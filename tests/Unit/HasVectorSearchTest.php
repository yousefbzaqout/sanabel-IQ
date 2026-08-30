<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MaterialChunk;
use App\Models\ParentMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasVectorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearest_neighbors_orders_chunks_by_cosine_distance(): void
    {
        $material = ParentMaterial::factory()->for(User::factory())->create();

        $far = MaterialChunk::factory()->for($material)->create([
            'chunk_index' => 0,
            'embedding' => $this->oneHot(200),
        ]);
        $near = MaterialChunk::factory()->for($material)->create([
            'chunk_index' => 1,
            'embedding' => $this->oneHot(0),
        ]);
        $mid = MaterialChunk::factory()->for($material)->create([
            'chunk_index' => 2,
            'embedding' => $this->mixed(0, 1, 0.8, 0.2),
        ]);

        $results = MaterialChunk::query()
            ->nearestNeighbors($this->oneHot(0), 2)
            ->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->first()->is($near));
        $this->assertTrue($results->last()->is($mid));
        $this->assertFalse($results->contains(fn (MaterialChunk $chunk): bool => $chunk->is($far)));
    }

    /**
     * @return list<float>
     */
    private function oneHot(int $index): array
    {
        $vector = array_fill(0, 768, 0.0);
        $vector[$index] = 1.0;

        return $vector;
    }

    /**
     * @return list<float>
     */
    private function mixed(int $primary, int $secondary, float $primaryWeight, float $secondaryWeight): array
    {
        $vector = array_fill(0, 768, 0.0);
        $vector[$primary] = $primaryWeight;
        $vector[$secondary] = $secondaryWeight;

        return $vector;
    }
}
