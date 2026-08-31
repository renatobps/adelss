<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
        'responsible_name',
        'responsible_phone',
        'registration_success_message',
        'send_receipt_pdf',
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
        'send_receipt_pdf' => 'boolean',
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
        if (!$this->banner_image) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $this->banner_image));
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = preg_replace('#^(?:/+)?storage/+#', '', $path);

        return asset('storage/' . ltrim($path, '/'));
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
     * Culto, PGI e Santa Ceia — recorrência semanal na home.
     */
    public function scopeAgendaSemanal($query)
    {
        return $query->whereHas('category', function ($cq) {
            $cq->where(function ($c2) {
                $c2->whereRaw('LOWER(name) = ?', ['culto'])
                    ->orWhereRaw('LOWER(name) = ?', ['pgi'])
                    ->orWhereRaw('LOWER(name) = ?', ['santa ceia']);
            });
        });
    }

    /**
     * "Eventos do mês" na home: o que o módulo Agenda > Eventos publica.
     * Não depende só da categoria "Eventos" — um evento com página pública
     * entra mesmo sem categoria definida, desde que não seja culto/PGI.
     */
    public function scopeEventosDoMes($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('category', fn ($c) => $c->whereRaw('LOWER(name) = ?', ['eventos']));

            $q->orWhere(function ($q2) {
                $q2->whereNotNull('public_slug')
                    ->where('public_slug', '!=', '')
                    ->where(function ($q3) {
                        $q3->whereNull('category_id')
                            ->orWhereHas('category', function ($cq) {
                                $cq->whereRaw('LOWER(name) NOT LIKE ?', ['%culto%'])
                                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%pgi%'])
                                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%santa ceia%']);
                            });
                    });
            });
        });
    }

    /**
     * Eventos exibidos na página principal pública (legado — preferir agendaSemanal/eventosDoMes).
     * @deprecated
     */
    public function scopeParaPaginaPrincipal($query)
    {
        $idsEscalas = collect();

        if (Schema::hasTable('monthly_culto_schedules')) {
            $idsEscalas = $idsEscalas->merge(
                MonthlyCultoSchedule::query()->pluck('event_id')
            );
        }

        if (Schema::hasTable('moriah_schedules')) {
            $idsEscalas = $idsEscalas->merge(
                MoriahSchedule::query()->whereNotNull('event_id')->pluck('event_id')
            );
        }

        $idsEscalas = $idsEscalas->unique()->filter()->values();

        return $query
            ->when($idsEscalas->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $idsEscalas->all()))
            ->whereRaw('LOWER(COALESCE(title, "")) NOT LIKE ?', ['%culto%'])
            ->where(function ($q) {
                $q->whereNull('category_id')
                    ->orWhereHas('category', function ($cq) {
                        $cq->whereRaw('LOWER(name) NOT LIKE ?', ['%culto%'])
                            ->whereRaw('LOWER(name) NOT LIKE ?', ['%santa ceia%']);
                    });
            });
    }

    /**
     * Eventos do módulo Agenda > Eventos (landing pública / inscrições).
     * Inclui eventos com slug público, categoria "Eventos" ou avulsos que não são culto/PGI.
     */
    public function scopeApenasEventosGerais($query)
    {
        $idsEscalas = collect();

        if (Schema::hasTable('monthly_culto_schedules')) {
            $idsEscalas = $idsEscalas->merge(
                MonthlyCultoSchedule::query()->pluck('event_id')
            );
        }

        if (Schema::hasTable('moriah_schedules')) {
            $idsEscalas = $idsEscalas->merge(
                MoriahSchedule::query()->whereNotNull('event_id')->pluck('event_id')
            );
        }

        $idsEscalas = $idsEscalas->unique()->filter()->values();

        return $query->where(function ($q) use ($idsEscalas) {
            $q->where(function ($q2) {
                $q2->whereNotNull('public_slug')
                    ->where('public_slug', '!=', '');
            });

            $q->orWhereHas('category', fn ($c) => $c->whereRaw('LOWER(name) = ?', ['eventos']));

            $q->orWhere(function ($q2) use ($idsEscalas) {
                if ($idsEscalas->isNotEmpty()) {
                    $q2->whereNotIn('id', $idsEscalas->all());
                }

                $q2->whereRaw('LOWER(COALESCE(title, "")) NOT LIKE ?', ['%culto%'])
                    ->whereRaw('LOWER(COALESCE(title, "")) NOT LIKE ?', ['%pgi%'])
                    ->where(function ($q3) {
                        $q3->whereNull('category_id')
                            ->orWhereHas('category', function ($cq) {
                                $cq->whereRaw('LOWER(name) NOT LIKE ?', ['%culto%'])
                                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%pgi%']);
                            });
                    });
            });
        });
    }

    /**
     * Cultos da Agenda (título ou categoria contendo "culto").
     */
    public function scopeCultosDaAgenda($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw('LOWER(COALESCE(title, "")) LIKE ?', ['%culto%'])
                ->orWhereHas('category', function ($cq) {
                    $cq->whereRaw('LOWER(name) LIKE ?', ['%culto%']);
                });
        });
    }

    /**
     * Cultos já ocorridos ou de hoje, desde 01/01/2016 (sem datas futuras).
     */
    public function scopeParaRelatorioFinanceiro($query)
    {
        return $query->cultosDaAgenda()
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('start_date', '>=', '2016-01-01')
            ->orderByDesc('start_date');
    }

    public function isCultoDaAgenda(): bool
    {
        return static::query()->cultosDaAgenda()->whereKey($this->id)->exists();
    }

    public static function paraLancamentoFinanceiro(int $dias = 45)
    {
        return static::query()
            ->cultosDaAgenda()
            ->whereBetween('start_date', [now()->subDays($dias)->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderByDesc('start_date')
            ->get();
    }

    public function getDisplayNameAttribute(): string
    {
        $when = $this->start_date?->format('d/m/Y H:i');

        return trim(($when ? $when.' · ' : '').($this->title ?: 'Culto'));
    }
}
