<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'content', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => 'array', // 🔥 Vector ko array mein convert karo
        ];
    }

    public function chunks()
{
    return $this->hasMany(DocumentChunk::class);
}
}