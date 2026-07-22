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

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime_type, 'video/');
    }

    /** foto|video|documento — útil para o seletor do Instagram. */
    public function mediaKind(): string
    {
        if ($this->isVideo()) {
            return 'video';
        }
        if ($this->isPhoto()) {
            return 'foto';
        }

        return self::CATEGORY_DOCUMENT;
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

    /** Classe CSS do ícone colorido por tipo (pdf/docx/planilha/vídeo/genérico). */
    public function fileTypeIconClass(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $ext = strtolower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION));

        if ($this->isVideo() || str_starts_with($mime, 'video/')) {
            return 'midia-ftype-video';
        }
        if (str_contains($mime, 'pdf') || $ext === 'pdf') {
            return 'midia-ftype-pdf';
        }
        if (
            str_contains($mime, 'word') || str_contains($mime, 'msword')
            || in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true)
        ) {
            return 'midia-ftype-doc';
        }
        if (
            str_contains($mime, 'sheet') || str_contains($mime, 'excel') || str_contains($mime, 'csv')
            || in_array($ext, ['xls', 'xlsx', 'ods', 'csv'], true)
        ) {
            return 'midia-ftype-sheet';
        }
        if (
            str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')
            || in_array($ext, ['ppt', 'pptx'], true)
        ) {
            return 'midia-ftype-ppt';
        }

        return 'midia-ftype-generic';
    }

    public function fileTypeIcon(): string
    {
        return match ($this->fileTypeIconClass()) {
            'midia-ftype-video' => 'bx-video',
            'midia-ftype-pdf' => 'bxs-file-pdf',
            'midia-ftype-doc' => 'bxs-file-doc',
            'midia-ftype-sheet' => 'bxs-file',
            'midia-ftype-ppt' => 'bxs-file',
            default => 'bx-file',
        };
    }
}
