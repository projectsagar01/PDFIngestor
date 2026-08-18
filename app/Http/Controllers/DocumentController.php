<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Models\DocumentChunk;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        // 🔥 Create document (pending status)
        $document = Document::create([
            'title' => $request->title,
            'content' => $request->content,
            'status' => 'pending',
        ]);

        // 🔥 Dispatch job (async)
        ProcessDocument::dispatch($document);

        return response()->json([
            'message' => 'Document uploaded. Processing in background.',
            'document_id' => $document->id,
        ]);
    }

    public function status($id)
    {
        $document = Document::with('chunks')->findOrFail($id);

        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'status' => $document->status ?? 'pending',
            'chunks_count' => $document->chunks->count(),
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        $results = DocumentChunk::query()
            ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.3)
            ->limit(5)
            ->get(['content']);

        return response()->json($results);
    }
}