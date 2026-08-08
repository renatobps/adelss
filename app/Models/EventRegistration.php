<?php

namespace App\Models;

use App\Services\EventRegistrationReceiptService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class EventRegistration extends Model
{
    use SoftDeletes;

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_CONFIRMADO = 'confirmado';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUSES = [
        self::STATUS_PENDENTE => 'Pendente',
        self::STATUS_CONFIRMADO => 'Confirmado',
        self::STATUS_CANCELADO => 'Cancelado',
    ];

    /** Janela em que dois envios do mesmo contato são tratados como duplo clique. */
    public const DOUBLE_SUBMIT_WINDOW_MINUTES = 5;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'address',
        'custom_answers',
        'status',
        'registration_number',
        'check_in_token',
        'receipt_sent_at',
        'checked_in_at',
        'checked_in_by',
        'deleted_by',
    ];

    protected $casts = [
        'custom_answers' => 'array',
        'receipt_sent_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // O número de inscrição é o identificador da pessoa no evento: existe desde
        // o primeiro segundo, independentemente de status ou pagamento.
        static::created(function (self $registration) {
            try {
                app(EventRegistrationReceiptService::class)->ensureCredentials($registration);
            } catch (\Throwable $e) {
                Log::warning('Evento: falha ao gerar número/token da inscrição.', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(EventRegistrationPayment::class, 'event_registration_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function isPaymentApproved(): bool
    {
        if (!$this->event?->is_paid) {
            return true;
        }

        return strtolower((string) ($this->payment?->status ?? '')) === 'approved';
    }

    /**
     * Pagamento confirmado impede exclusão: apagar o registro quebraria a
     * conciliação financeira do evento.
     */
    public function hasConfirmedPayment(): bool
    {
        return strtolower((string) ($this->payment?->status ?? '')) === 'approved';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        $digits = preg_replace('/\D+/', '', $term);

        return $query->where(function (Builder $q) use ($like, $digits) {
            $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('registration_number', 'like', $like);

            if ($digits !== '' && strlen($digits) >= 4) {
                $q->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?",
                    ['%' . $digits . '%']
                );
            }
        });
    }

    /** Inscrições que ocupam vaga (tudo que não foi cancelado). */
    public function scopeEmVaga(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDENTE, self::STATUS_CONFIRMADO]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Pendente';
    }

    /** Iniciais determinísticas (ex.: Renato Bento Pereira → RP). */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));
        if ($parts === []) {
            return '?';
        }
        $first = mb_substr($parts[0], 0, 1);
        $second = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first . $second);
    }

    /** Cor de fundo estável a partir do nome. */
    public function getAvatarColorAttribute(): string
    {
        $palette = ['#0088CC', '#14B8A6', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899', '#10B981', '#6366F1'];
        $hash = crc32(mb_strtolower(trim((string) $this->name)));

        return $palette[abs($hash) % count($palette)];
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);
        if ($digits === '' || strlen($digits) < 10) {
            return null;
        }
        if (! str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        return 'https://wa.me/' . $digits;
    }

    /**
     * Chave usada para agrupar registros do mesmo contato dentro de um evento.
     */
    public function duplicateKeys(): array
    {
        $keys = [];
        $email = mb_strtolower(trim((string) $this->email));
        if ($email !== '') {
            $keys[] = 'email:' . $email;
        }
        $phone = preg_replace('/\D+/', '', (string) $this->phone);
        if ($phone !== '') {
            $keys[] = 'phone:' . $phone;
        }

        return $keys;
    }
}
