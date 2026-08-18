<?php

return [

    'default' => env('AI_PROVIDER', 'openai'),
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'ollama',  // 🔥 CRITICAL
    'default_for_reranking' => 'cohere',

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    // 🔥 EMBEDDINGS CONFIG (MUST BE TOP-LEVEL)
    'embeddings' => [
        'default' => 'ollama',  // 🔥 HARDCODE FOR TESTING
    ],

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],
        'azure' => [ /* ... */ ],
        'bedrock' => [ /* ... */ ],
        'cohere' => [ /* ... */ ],
        'deepseek' => [ /* ... */ ],
        'eleven' => [ /* ... */ ],
        'gemini' => [ /* ... */ ],
        'groq' => [ /* ... */ ],
        'jina' => [ /* ... */ ],
        'mistral' => [ /* ... */ ],

        'ollama' => [
            'driver' => 'ollama',
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env('OLLAMA_URL', 'http://localhost:11434'),
            'model' => env('AI_EMBEDDINGS_MODEL', 'nomic-embed-text'), // 🔥 ADD THIS
        ],

        'openai' => [ /* ... */ ],
        'openai-compatible' => [ /* ... */ ],
        'openrouter' => [ /* ... */ ],
        'voyageai' => [ /* ... */ ],
        'xai' => [ /* ... */ ],
    ],
];