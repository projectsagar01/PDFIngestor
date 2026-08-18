# 🚀 Day 9 — Async Ingestion Pipeline (PDFIngestor)

> **Project:** Laravel AI Agent with Async Document Processing  
> **Goal:** Upload documents → Queue → Chunk → Embed → Store (all in background)  
> **Tech Stack:** Laravel, Laravel AI SDK, Ollama (nomic-embed-text), pgvector, Laravel Queues

---

## 📖 Project Overview

This project demonstrates how to build an **async document ingestion pipeline** in Laravel. When a user uploads a document:

1. Document is saved to database with `pending` status.
2. A background job (`ProcessDocument`) is dispatched to the queue.
3. The job chunks the document content into smaller pieces.
4. Each chunk is embedded using Ollama's `nomic-embed-text` model.
5. Chunks + embeddings are stored in the `document_chunks` table.
6. Document status is updated to `processed`.

**Why Async?** Real-world applications handle 500+ documents at once. Users can't wait for all documents to process. Queues enable background processing, freeing up the HTTP request.

---

## ⚠️ Important: Laravel AI SDK Version Issue

**This project uses Laravel AI SDK `v0.10.3`** (latest stable as of August 2026). There is a **known bug** in this version:

> **Embeddings generation via SDK fails** with `OpenAiProvider::embeddings(): Argument #1 ($inputs) must be of type array, string given`.

**Solution:** Raw HTTP calls to Ollama are used instead of the SDK's `Embeddings::generate()` method. The SDK is still used for `whereVectorSimilarTo()` search, which works perfectly.

---

## 🏗️ File Structure

```text
day9_project/
├── app/
│   ├── Ai/                           # AI Agents (empty for now)
│   ├── Console/
│   │   └── Commands/
│   │       └── SeedDocuments.php     # Seeds documents with embeddings
│   ├── Http/
│   │   └── Controllers/
│   │       └── DocumentController.php # Upload, Status, Search endpoints
│   ├── Jobs/
│   │   └── ProcessDocument.php       # 🔥 Background job for processing
│   ├── Models/
│   │   ├── Document.php              # Document model
│   │   └── DocumentChunk.php         # Chunk model (with embedding)
│   └── Services/
│       └── Chunker.php               # Text chunking service
├── bootstrap/
│   └── app.php                       # CSRF exceptions
├── config/
│   └── ai.php                        # AI provider config
├── database/
│   └── migrations/
│       ├── create_documents_table.php
│       └── create_document_chunks_table.php
├── routes/
│   └── web.php                       # Routes
└── .env                              # Database + AI config
```

---

## 🛠️ Prerequisites

| Requirement | Command / Link |
| :--- | :--- |
| **PHP 8.3+** | `php -v` |
| **Composer** | `composer --version` |
| **PostgreSQL 15+** | `psql --version` |
| **pgvector Extension** | [Installation Guide](https://github.com/pgvector/pgvector) |
| **Ollama** | `curl -fsSL https://ollama.com/install.sh \| sh` |
| **Ollama Embedding Model** | `ollama pull nomic-embed-text` |
| **Laravel AI SDK** | `composer require laravel/ai` |
| **pgvector PHP Package** | `composer require pgvector/pgvector` |

---

## 📦 Installation & Setup

### 1. Clone and Install

```bash
git clone <your-repo-url>
cd day9_project
composer install
cp .env.example .env
```

### 2. Database Setup (PostgreSQL)

**.env:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=day9_project
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 3. Enable pgvector Extension

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

### 4. AI Provider Configuration

**.env:**
```env
AI_PROVIDER=ollama
AI_MODEL=llama3.1
OLLAMA_URL=http://localhost:11434
AI_EMBEDDINGS_PROVIDER=ollama
AI_EMBEDDINGS_MODEL=nomic-embed-text
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Run Queue Worker (Background)

```bash
php artisan queue:work
```

### 7. Start Laravel Server

```bash
php artisan serve
```

---

## 🐞 Debugging Guide — Common Errors & Fixes

### Error 1: `SQLSTATE[23502]: Not null violation: null value in column "embedding"`

**Why:** `embedding` column is NOT NULL, but you're creating a document without an embedding.

**Fix:** Make the column nullable:

```bash
php artisan make:migration make_embedding_nullable_in_documents --table=documents
```

```php
public function up()
{
    Schema::table('documents', function (Blueprint $table) {
        $table->vector('embedding', 768)->nullable()->change();
    });
}
```

```bash
php artisan migrate
```

---

### Error 2: `419 Page Expired` (CSRF Token Mismatch)

**Why:** Laravel's CSRF protection blocks POST requests without a token.

**Fix:** Disable CSRF for the upload route in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'documents/upload',
    ]);
})
```

**Note:** For production, use CSRF tokens or API tokens instead of disabling.

---

### Error 3: `OllamaProvider::embeddings(): Argument #1 must be array, string given`

**Why:** Laravel AI SDK v0.10.3 has a bug in the `for()` method.

**Fix:** Use raw HTTP calls instead of the SDK for embedding generation.

```php
// ❌ SDK (Doesn't work)
$embedding = Ai::embeddings('ollama')->for([$chunk])->generate();

// ✅ Raw HTTP (Works)
$response = Http::post('http://localhost:11434/api/embeddings', [
    'model' => 'nomic-embed-text',
    'prompt' => $chunk,
]);
$embedding = $response->json()['embedding'] ?? [];
```

---

### Error 4: `Class "App\Jobs\ProcessDocument" not found`

**Why:** The job class was created but Composer's autoloader hasn't registered it.

**Fix:**

```bash
composer dump-autoload
```

---

### Error 5: `Class "Laravel\Ai\Facades\Ai" not found`

**Why:** The AI facade alias is not registered.

**Fix:** Use `AiManager` directly:

```php
$ai = app(\Laravel\Ai\AiManager::class);
$embedding = $ai->embeddings('ollama')
    ->for([$chunk])
    ->generate();
```

**Or** add alias in `config/app.php`:

```php
'aliases' => [
    'Ai' => Laravel\Ai\Facades\Ai::class,
],
```

---

### Error 6: `401 Unauthorized` (OpenAI API)

**Why:** Default provider is OpenAI, but you don't have an OpenAI API key.

**Fix:** Set `.env`:

```env
AI_PROVIDER=ollama
AI_EMBEDDINGS_PROVIDER=ollama
```

---

### Error 7: `expected 1536 dimensions, not 768`

**Why:** Migration expects 1536 dimensions (OpenAI), but Ollama generates 768 dimensions.

**Fix:** Change migration:

```php
$table->vector('embedding', 768);   // 🔥 768 for Ollama
```

---

### Error 8: `SQLSTATE[22000]: Data exception: 7 ERROR: expected 1536 dimensions`

Same as above — change the dimensions to `768` in all migrations.

---

## 🔍 How to Check if the Project is Working

### 1. Check Queue Worker

```bash
php artisan queue:work
```

**Expected Output:**
```
Processing jobs from the [default] queue.
App\Jobs\ProcessDocument ......... RUNNING
App\Jobs\ProcessDocument ......... DONE
```

### 2. Test Upload via curl

```bash
curl -X POST http://127.0.0.1:8000/documents/upload \
-H "Content-Type: application/json" \
-d '{"title":"Test","content":"Return items within 30 days."}'
```

**Expected Output:**
```json
{"message":"Document uploaded. Processing in background.","document_id":1}
```

### 3. Check Database via Tinker

```bash
php artisan tinker
```

```php
// Check document
\App\Models\Document::find(1);

// Check chunks
\App\Models\DocumentChunk::all();

// Check status
\App\Models\Document::where('status', 'processed')->get();
```

### 4. Search via API

```bash
curl "http://127.0.0.1:8000/search?q=How to return an item?"
```

**Expected Output:** Chunks related to the query.

---

## 🧪 Step-by-Step Test Flow

| Step | Action | Expected Result |
| :--- | :--- | :--- |
| 1 | `php artisan queue:work` | Worker running |
| 2 | Upload document | `{"message":"Document uploaded..."}` |
| 3 | Check queue worker | `App\Jobs\ProcessDocument ... RUNNING ... DONE` |
| 4 | Check database | Chunks with embeddings stored |
| 5 | Search | Results returned |

---

## 🧠 Interview Questions (Be Ready)

**Q: *"How do you handle large document uploads without timing out?"***

**A:** *"I use async ingestion. The document is saved immediately, and a background job processes it. The user gets an immediate response with a job ID. Chunking, embedding, and storage happen in the background via Laravel Queues."*

**Q: *"What is backpressure, and how do you handle it?"***

**A:** *"Backpressure is rate-limiting the queue workers to prevent database overload. I use `--max-jobs=100` or `--max-time=3600` to control worker load."*

**Q: *"Why are you using raw HTTP instead of the SDK for embeddings?"** *

**A:** *"Laravel AI SDK v0.10.3 has a known bug in the `for()` method. Raw HTTP calls are more reliable and give me full control over the request/response cycle."*

**Q: *"How do you debug a failed job?"** *

**A:** *"I check the failed jobs table with `php artisan queue:failed`, inspect the logs with `tail -f storage/logs/laravel.log`, and retry with `php artisan queue:retry all`."*

---

## 📌 Key Takeaways

1. **Async ingestion** prevents HTTP timeouts for large documents.
2. **Laravel Queues** enable background processing.
3. **Chunking** keeps content within token limits.
4. **Embeddings** enable semantic search.
5. **pgvector** stores and queries embeddings efficiently.
6. **SDK v0.10.3 has bugs** — use raw HTTP for embeddings.
7. **Always run `composer dump-autoload`** after creating new classes.

---

## 🚀 Next Steps

- **Day 10:** RAG Pipeline — Query documents with AI and citations.
- **Day 11:** KnowledgeBot — Complete RAG with answer generation.

---

**Made with ❤️ by Sagar — Laravel AI Engineer** 🐇🔥