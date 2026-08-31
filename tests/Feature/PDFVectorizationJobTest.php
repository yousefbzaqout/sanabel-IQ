<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\AIServiceInterface;
use App\Enums\MaterialStatus;
use App\Jobs\ProcessPDFMaterialJob;
use App\Models\MaterialChunk;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\User;
use App\Services\Document\TextChunker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\ValueObjects\Embedding;
use Tests\Support\SamplePdfFactory;
use Tests\TestCase;

class PDFVectorizationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('materials');
    }

    public function test_process_pdf_material_job_extracts_text_chunks_and_stores_embeddings(): void
    {
        $embedding = array_fill(0, 768, 0.123);

        Prism::fake([
            EmbeddingsResponseFake::make()->withEmbeddings([
                Embedding::fromArray($embedding),
            ]),
            EmbeddingsResponseFake::make()->withEmbeddings([
                Embedding::fromArray($embedding),
            ]),
            EmbeddingsResponseFake::make()->withEmbeddings([
                Embedding::fromArray($embedding),
            ]),
        ]);

        $material = $this->createMaterialWithFixturePdf();

        ProcessPDFMaterialJob::dispatchSync($material);

        $material->refresh();

        $this->assertSame(MaterialStatus::Completed, $material->status);

        $chunks = MaterialChunk::query()
            ->where('parent_material_id', $material->id)
            ->orderBy('chunk_index')
            ->get();

        $this->assertGreaterThanOrEqual(1, $chunks->count());
        $this->assertSame(0, $chunks->first()->chunk_index);

        if ($chunks->count() > 1) {
            $this->assertSame(1, $chunks->get(1)->chunk_index);
        }

        foreach ($chunks as $chunk) {
            $this->assertNotSame('', trim($chunk->content));
            $this->assertNotNull($chunk->embedding);
            $this->assertStringStartsWith('[', (string) $chunk->getRawOriginal('embedding'));
        }
    }

    public function test_process_pdf_material_job_handles_extraction_failure_gracefully(): void
    {
        Log::spy();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $filePath = 'corrupted.pdf';
        Storage::disk('materials')->put($filePath, 'not-a-valid-pdf');

        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => $filePath,
            'status' => MaterialStatus::Pending,
        ]);

        ProcessPDFMaterialJob::dispatchSync($material);

        $material->refresh();

        $this->assertSame(MaterialStatus::Failed, $material->status);
        $this->assertDatabaseCount('material_chunks', 0);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_vector_search_returns_relevant_material_chunks(): void
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

        $results = MaterialChunk::nearestNeighbors($this->oneHot(0), limit: 3);

        $this->assertCount(3, $results);
        $this->assertTrue($results->first()->is($near));
        $this->assertTrue($results->get(1)->is($mid));
        $this->assertTrue($results->last()->is($far));
    }

    public function test_textless_pdf_fails_with_clear_log_message(): void
    {
        Log::spy();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $filePath = 'image-only.pdf';
        Storage::disk('materials')->put($filePath, SamplePdfFactory::createWithoutExtractableText());

        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => $filePath,
            'status' => MaterialStatus::Pending,
        ]);

        ProcessPDFMaterialJob::dispatchSync($material);

        $material->refresh();

        $this->assertSame(MaterialStatus::Failed, $material->status);
        $this->assertDatabaseCount('material_chunks', 0);
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'PDF material processing failed.',
                \Mockery::on(fn (array $context): bool => str_contains(
                    (string) ($context['message'] ?? ''),
                    'No extractable text found in PDF.',
                )),
            );
    }

    public function test_large_pdf_produces_sequential_chunk_indexes_and_valid_vectors(): void
    {
        $longText = str_repeat(
            'Sanabel large PDF chunk indexing validation sentence for mathematics and science curriculum. ',
            280,
        );
        $expectedChunkCount = count((new TextChunker)->chunk(trim($longText)));
        $this->assertGreaterThan(20, $expectedChunkCount);

        $embeddingResponses = [];
        for ($index = 0; $index < $expectedChunkCount; $index++) {
            $embeddingResponses[] = EmbeddingsResponseFake::make()->withEmbeddings([
                Embedding::fromArray(array_fill(0, 768, 0.123)),
            ]);
        }

        Prism::fake($embeddingResponses);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $filePath = 'large-material.pdf';
        Storage::disk('materials')->put($filePath, SamplePdfFactory::create(trim($longText)));

        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => $filePath,
            'status' => MaterialStatus::Pending,
        ]);

        ProcessPDFMaterialJob::dispatchSync($material);

        $material->refresh();
        $this->assertSame(MaterialStatus::Completed, $material->status);

        $chunks = MaterialChunk::query()
            ->where('parent_material_id', $material->id)
            ->orderBy('chunk_index')
            ->get();

        $this->assertSame($expectedChunkCount, $chunks->count());

        foreach ($chunks as $index => $chunk) {
            $this->assertSame($index, $chunk->chunk_index);
            $this->assertNotSame('', trim($chunk->content));

            $rawEmbedding = (string) $chunk->getRawOriginal('embedding');
            $this->assertStringStartsWith('[', $rawEmbedding);
            $this->assertSame(768, count(json_decode($rawEmbedding, true, 512, JSON_THROW_ON_ERROR)));
        }
    }

    public function test_embedding_api_failure_marks_material_failed_without_partial_chunks(): void
    {
        Log::spy();

        $longText = str_repeat('Chunked embedding failure rollback validation text. ', 40);
        $chunkCount = count((new TextChunker)->chunk(trim($longText)));
        $this->assertGreaterThanOrEqual(3, $chunkCount);

        $calls = 0;
        $this->mock(AIServiceInterface::class, function ($mock) use (&$calls): void {
            $mock->shouldReceive('generateEmbedding')
                ->andReturnUsing(function () use (&$calls): array {
                    $calls++;

                    if ($calls === 3) {
                        throw new \RuntimeException('HTTP 429 Too Many Requests');
                    }

                    return array_fill(0, 768, 0.456);
                });
        });

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $filePath = 'rate-limited.pdf';
        Storage::disk('materials')->put($filePath, SamplePdfFactory::create(trim($longText)));

        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => $filePath,
            'status' => MaterialStatus::Pending,
        ]);

        ProcessPDFMaterialJob::dispatchSync($material);

        $material->refresh();

        $this->assertSame(MaterialStatus::Failed, $material->status);
        $this->assertDatabaseCount('material_chunks', 0);
        $this->assertSame(3, $calls);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_semantic_vector_search_returns_matching_arabic_chunk_first(): void
    {
        $material = ParentMaterial::factory()->for(User::factory())->create();

        $triangleChunk = MaterialChunk::factory()->for($material)->create([
            'content' => 'حساب مساحة المثلث',
            'chunk_index' => 0,
            'embedding' => $this->oneHot(42),
        ]);
        MaterialChunk::factory()->for($material)->create([
            'content' => 'عواصم الدول العربية',
            'chunk_index' => 1,
            'embedding' => $this->oneHot(500),
        ]);

        $results = MaterialChunk::nearestNeighbors($this->oneHot(42), limit: 2);

        $this->assertTrue($results->first()->is($triangleChunk));
        $this->assertSame('حساب مساحة المثلث', $results->first()->content);
    }

    public function test_process_pdf_material_job_exposes_queue_retry_configuration(): void
    {
        $material = ParentMaterial::factory()->create();
        $job = new ProcessPDFMaterialJob($material);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff);
    }

    private function createMaterialWithFixturePdf(): ParentMaterial
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $filePath = 'sample-material.pdf';
        $longText = str_repeat(
            'Sanabel sample PDF for vectorization testing. Algebra geometry measurement and reading comprehension. ',
            20,
        );

        Storage::disk('materials')->put(
            $filePath,
            SamplePdfFactory::create(trim($longText)),
        );

        return ParentMaterial::factory()->for($parent)->for($student)->create([
            'file_path' => $filePath,
            'status' => MaterialStatus::Pending,
        ]);
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
