<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AIServiceInterface;
use App\Enums\MaterialStatus;
use App\Models\MaterialChunk;
use App\Models\ParentMaterial;
use App\Services\Document\PDFTextExtractor;
use App\Services\Document\TextChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPDFMaterialJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public ParentMaterial $parentMaterial) {}

    public function handle(
        PDFTextExtractor $pdfTextExtractor,
        TextChunker $textChunker,
        AIServiceInterface $aiService,
    ): void {
        $material = $this->parentMaterial->fresh();

        if ($material === null) {
            return;
        }

        $material->update(['status' => MaterialStatus::Processing]);

        try {
            DB::transaction(function () use ($material, $pdfTextExtractor, $textChunker, $aiService): void {
                $text = $pdfTextExtractor->extract($material->file_path);
                $chunks = $textChunker->chunk($text);

                if ($chunks === []) {
                    throw new \RuntimeException('Text chunking produced no segments.');
                }

                foreach ($chunks as $index => $content) {
                    $embedding = $aiService->generateEmbedding($content);

                    MaterialChunk::query()->create([
                        'parent_material_id' => $material->id,
                        'content' => $content,
                        'embedding' => $embedding,
                        'chunk_index' => $index,
                    ]);
                }

                $material->update(['status' => MaterialStatus::Completed]);
            });
        } catch (Throwable $exception) {
            Log::error('PDF material processing failed.', [
                'parent_material_id' => $material->id,
                'message' => $exception->getMessage(),
            ]);

            $material->update(['status' => MaterialStatus::Failed]);
        }
    }
}
