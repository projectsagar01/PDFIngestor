<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedDocuments extends Command
{
    protected $signature = 'documents:seed';
    protected $description = 'Seed documents with embeddings';

    public function handle(): void
    {
        $faqs = [
            ['title' => 'Return Policy', 'content' => 'You can return items within 30 days. Items must be unused.'],
            ['title' => 'Shipping Policy', 'content' => 'Free shipping on orders over $50. Delivery in 3-5 business days.'],
            ['title' => 'Warranty', 'content' => 'All products have a 1-year warranty against manufacturing defects.'],
            ['title' => 'Refund Policy', 'content' => 'Refunds are processed within 5-7 business days after return approval.'],
        ];

        foreach ($faqs as $faq) {
            // 🔥 Direct HTTP call (SDK is failing, this works)
            $response = Http::post('http://localhost:11434/api/embeddings', [
                'model' => 'nomic-embed-text',
                'prompt' => $faq['content'],
            ]);

            $embedding = $response->json()['embedding'] ?? [];

            Document::create([
                'title' => $faq['title'],
                'content' => $faq['content'],
                'embedding' => $embedding,
            ]);

            $this->info('✅ Embedded: ' . $faq['title']);
        }

        $this->info('✅ All documents seeded with embeddings!');
    }
}