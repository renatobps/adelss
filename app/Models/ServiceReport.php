<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceReport extends Model
{
    public const STATUS_DRAFT = 'rascunho';
    public const STATUS_FINAL = 'finalizado';

    public const TYPES = [
        'celebracao' => 'Celebração',
        'oracao' => 'Oração',
        'ensino' => 'Ensino',
        'jovens' => 'Jovens',
        'evangelistico' => 'Evangelístico',
        'missionario' => 'Missionário',
        'criancas' => 'Crianças',
        'santa_ceia' => 'Santa Ceia',
        'conferencia' => 'Conferência',
        'vigilia' => 'Vigília',
        'outro' => 'Outro',
    ];

    protected $fillable = [
        'event_id',
        'report_date',
        'service_type',
        'custom_type_label',
        'start_time',
        'preacher_member_id',
        'external_preacher_name',
        'message_theme',
        'campaign_series',
        'description',
        'highlights',
        'members_present_count',
        'visitors_count',
        'children_count',
        'volunteers_count',
        'offering_total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'offering_total' => 'decimal:2',
        'members_present_count' => 'integer',
        'visitors_count' => 'integer',
        'children_count' => 'integer',
        'volunteers_count' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function preacher(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'preacher_member_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ServiceReportAttendance::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(ServiceReportVisitor::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ServiceReportOffering::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ServiceReportPhoto::class);
    }

    public function spiritualDecisions(): HasMany
    {
        return $this->hasMany(ServiceReportSpiritualDecision::class);
    }

    public function getServiceTypeLabelAttribute(): string
    {
        if ($this->service_type === 'outro' && $this->custom_type_label) {
            return $this->custom_type_label;
        }

        return self::TYPES[$this->service_type] ?? $this->service_type;
    }

    public function getPreacherNameAttribute(): string
    {
        return $this->external_preacher_name
            ?: ($this->preacher?->name ?? '—');
    }

    public function presentMembersCount(): int
    {
        if ($this->members_present_count !== null) {
            return (int) $this->members_present_count;
        }

        return (int) $this->attendances()->where('present', true)->count();
    }

    public function resolvedVisitorsCount(): int
    {
        if ($this->visitors_count !== null) {
            return (int) $this->visitors_count;
        }

        return (int) $this->visitors()->count();
    }

    public function totalPresent(): int
    {
        return $this->presentMembersCount() + $this->resolvedVisitorsCount();
    }
}
