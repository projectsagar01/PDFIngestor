<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\Chunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 10, 30];

    protected Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(Chunker $chunker): void
    {
        // 🔥 Step 1: Chunk
        $chunks = $chunker->chunk($this->document->content);

        foreach ($chunks as $index => $chunk) {
            // 🔥 Step 2: Embed each chunk
            $response = Http::post('http://localhost:11434/api/embeddings', [
                'model' => 'nomic-embed-text',
                'prompt' => $chunk,
            ]);

            $embedding = $response->json()['embedding'] ?? [];

            // 🔥 Step 3: Store chunk
            DocumentChunk::create([
                'document_id' => $this->document->id,
                'chunk_index' => $index,
                'content' => $chunk,
                'embedding' => $embedding,
            ]);
        }

        // 🔥 Step 4: Update document status
        $this->document->update(['status' => 'processed']);

        Log::info('✅ Document #' . $this->document->id . ' processed with ' . count($chunks) . ' chunks.');
    }
}