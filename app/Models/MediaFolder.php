<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFolder extends Model
{
    protected $fillable = [
        'name',
        'google_drive_folder_id',
        'parent_folder_id',
        'department_id',
        'created_by',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_folder_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_folder_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'media_folder_id');
    }

    /** @return list<self> */
    public function breadcrumb(): array
    {
        $crumbs = [];
        $current = $this;
        while ($current) {
            array_unshift($crumbs, $current);
            $current = $current->parent;
        }

        return $crumbs;
    }

    /** Faixa de cor do card (heurística pelo nome). */
    public function accentClass(): string
    {
        $name = mb_strtolower($this->name);

        if (preg_match('/foto|banner|imagem|galeria|culto|evento|midia|mídia/', $name)) {
            return 'midia-folder-accent-blue';
        }
        if (preg_match('/doc|documento|ata|relatorio|relatório|pdf/', $name)) {
            return 'midia-folder-accent-dark';
        }
        if (preg_match('/video|vídeo|reels|story|stories/', $name)) {
            return 'midia-folder-accent-ig';
        }

        $palette = [
            'midia-folder-accent-blue',
            'midia-folder-accent-teal',
            'midia-folder-accent-dark',
            'midia-folder-accent-amber',
        ];

        return $palette[crc32($this->name) % count($palette)];
    }

    public function itemsCountLabel(): string
    {
        $files = (int) ($this->files_count ?? $this->files()->count());
        $folders = (int) ($this->children_count ?? $this->children()->count());
        $total = $files + $folders;

        if ($total === 0) {
            return 'Vazia';
        }

        $parts = [];
        if ($folders > 0) {
            $parts[] = $folders . ' ' . ($folders === 1 ? 'pasta' : 'pastas');
        }
        if ($files > 0) {
            $parts[] = $files . ' ' . ($files === 1 ? 'arquivo' : 'arquivos');
        }

        return implode(' · ', $parts);
    }
}
