<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'all_day',
        'recurrence',
        'visibility',
        'status',
        'location',
        'category_id',
        'public_slug',
        'subtitle',
        'subtitle_color',
        'subtitle_font_family',
        'page_palette',
        'banner_image',
        'about_html',
        'is_paid',
        'price',
        'max_spots',
        'phone_required',
        'address_required',
        'email_required',
        'hide_phone',
        'hide_address',
        'notify_emails',
        'registration_enabled',
        'location_photos',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'phone_required' => 'boolean',
        'address_required' => 'boolean',
        'email_required' => 'boolean',
        'hide_phone' => 'boolean',
        'hide_address' => 'boolean',
        'registration_enabled' => 'boolean',
        'location_photos' => 'array',
    ];

    /**
     * Fontes permitidas para o tema (subtítulo) na página pública (Google Fonts).
     *
     * @return list<string>
     */
    public static function allowedSubtitleFontFamilies(): array
    {
        return [
            'Poppins',
            'Open Sans',
            'Montserrat',
            'Playfair Display',
            'Merriweather',
            'Roboto Slab',
            'Oswald',
        ];
    }

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function scheduleItems()
    {
        return $this->hasMany(EventScheduleItem::class)->orderBy('sort_order');
    }

    public function registrationFields()
    {
        return $this->hasMany(EventRegistrationField::class)->orderBy('sort_order');
    }

    public function speakers()
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('sort_order');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function registrationsCount(): int
    {
        return $this->registrations()->count();
    }

    /**
     * URL pública de arquivo salvo no disco "public" (ex.: events/banners/…).
     * Aceita caminho relativo, URLs absolutas ou valores com prefixo "storage/" duplicado.
     */
    public static function publicStorageUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = preg_replace('#^(?:/+)?storage/+#', '', $path);
        $path = ltrim($path, '/');

        $url = Storage::disk('public')->url($path);

        return self::normalizePublicUrl($url);
    }

    private static function normalizePublicUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return $url;
        }

        // Já está absoluto (http/https)
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        // URL protocol-relative (//dominio/arquivo)
        if (str_starts_with($url, '//')) {
            return request()->getScheme().':'.$url;
        }

        // Caminho absoluto relativo ao host atual (/storage/arquivo)
        if (str_starts_with($url, '/')) {
            return url($url);
        }

        // Caso APP_URL venha sem esquema (ex: dominio.com/storage/arquivo)
        if (preg_match('#^[^/]+\.[^/]+/#', $url)) {
            return request()->getScheme().'://'.$url;
        }

        return url('/'.ltrim($url, '/'));
    }

    public function bannerImagePublicUrl(): ?string
    {
        return self::publicStorageUrl($this->banner_image);
    }

    /**
     * @return list<string>
     */
    public function locationPhotoPublicUrls(): array
    {
        $photos = $this->location_photos;
        if ($photos === null || $photos === '') {
            return [];
        }
        if (is_string($photos)) {
            $decoded = json_decode($photos, true);
            $photos = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($photos)) {
            return [];
        }
        $urls = [];
        foreach ($photos as $p) {
            if (! is_string($p) || trim($p) === '') {
                continue;
            }
            $url = self::publicStorageUrl($p);
            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Apenas eventos “gerais” para o módulo /agenda/eventos:
     * exclui cultos (escalas mensais / Moriah ou título/categoria com “culto”)
     * e PGIs (título/categoria com “pgi”).
     */
    public function scopeApenasEventosGerais($query)
    {
        $idsCulto = MonthlyCultoSchedule::query()->pluck('event_id')
            ->merge(MoriahSchedule::query()->whereNotNull('event_id')->pluck('event_id'))
            ->unique()
            ->filter()
            ->values()
            ->all();

        return $query
            ->when(count($idsCulto) > 0, fn ($q) => $q->whereNotIn('id', $idsCulto))
            ->whereRaw('LOWER(title) NOT LIKE ?', ['%culto%'])
            ->whereRaw('LOWER(title) NOT LIKE ?', ['%pgi%'])
            ->whereDoesntHave('category', function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw('LOWER(name) LIKE ?', ['%culto%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%pgi%']);
                });
            });
    }
}
