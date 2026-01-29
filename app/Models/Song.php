<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Song extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'artist',
        'genre',
        'key',
        'folder_id',
        'thumbnail_url',
        'has_lyrics',
        'has_chords',
        'has_audio',
        'has_video',
        'lyrics',
        'chords',
        'audio_url',
        'video_url',
        'order',
    ];

    protected $casts = [
        'has_lyrics' => 'boolean',
        'has_chords' => 'boolean',
        'has_audio' => 'boolean',
        'has_video' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relacionamento com Pasta
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}
