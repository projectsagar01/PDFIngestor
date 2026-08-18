<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->vector('embedding', 768);   // 🔥 768 dimensions for Ollama
        $table->timestamps();
    });

    DB::statement('CREATE INDEX idx_documents_embedding_hnsw ON documents USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64);');
}

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};