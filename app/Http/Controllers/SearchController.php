<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        // 🔥 This method works — it doesn't use the embedding SDK, only pgvector
        $results = Document::query()
            ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.3)
            ->limit(3)
            ->get(['title', 'content']);

        return response()->json($results);
    }
}