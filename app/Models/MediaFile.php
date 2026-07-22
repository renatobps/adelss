<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFile extends Model
{
    public const CATEGORY_PHOTO = 'foto';
    public const CATEGORY_DOCUMENT = 'documento';

    protected $fillable = [
        'name',
        'original_filename',
        'mime_type',
        'size',
        'google_drive_file_id',
        'media_folder_id',
        'category',
        'module_reference',
        'reference_id',
        'description',
        'tags',
        'uploaded_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'size' => 'integer',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class, 'media_file_id');
    }

    public function isPhoto(): bool
    {
        return $this->category === self::CATEGORY_PHOTO
            || str_starts_with((string) $this->mime_type, 'image/');
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }
}
