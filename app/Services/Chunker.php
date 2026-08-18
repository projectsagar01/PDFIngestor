<?php

namespace App\Services;

class Chunker
{
    public function chunk(string $text, int $size = 500): array
    {
        // 🔥 Split by sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (strlen($current . $sentence) > $size) {
                if (!empty($current)) {
                    $chunks[] = trim($current);
                }
                $current = $sentence;
            } else {
                $current .= ' ' . $sentence;
            }
        }

        if (!empty($current)) {
            $chunks[] = trim($current);
        }

        return $chunks;
    }
}