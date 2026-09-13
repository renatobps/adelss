<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MonthlyCultoSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'month',
        'year',
        'status',
        'guest_preletor_name',
    ];

    /**
     * Relacionamento com Evento (Culto)
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relacionamento com Preletores (membros)
     */
    public function preletores()
    {
        return $this->belongsToMany(Member::class, 'monthly_culto_preletores', 'monthly_culto_schedule_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Dirigentes (membros)
     */
    public function dirigentes()
    {
        return $this->belongsToMany(Member::class, 'monthly_culto_dirigentes', 'monthly_culto_schedule_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Portaria (voluntários)
     */
    public function portaria()
    {
        return $this->belongsToMany(Volunteer::class, 'monthly_culto_portaria', 'monthly_culto_schedule_id', 'volunteer_id')
                    ->with('member')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Áreas de Serviço (genérico)
     */
    public function serviceAreaVolunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'monthly_culto_service_areas', 'monthly_culto_schedule_id', 'volunteer_id')
                    ->withPivot('id', 'service_area_id', 'status')
                    ->with('member')
                    ->withTimestamps();
    }

    /**
     * Obter voluntários de uma área de serviço específica
     */
    public function getVolunteersByServiceArea($serviceAreaId)
    {
        return $this->serviceAreaVolunteers()
                    ->wherePivot('service_area_id', $serviceAreaId)
                    ->orderBy('monthly_culto_service_areas.id')
                    ->get();
    }

    /**
     * Scope para filtrar por mês e ano
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public static function hasGuestPreletorColumn(): bool
    {
        static $exists;

        if ($exists === null) {
            $exists = Schema::hasTable((new static)->getTable())
                && Schema::hasColumn((new static)->getTable(), 'guest_preletor_name');
        }

        return $exists;
    }

    public function setGuestPreletorName(?string $name): void
    {
        if (! static::hasGuestPreletorColumn()) {
            return;
        }

        $this->update(['guest_preletor_name' => filled($name) ? trim($name) : null]);
    }

    public function guestPreletorNameForArea(?ServiceArea $area): ?string
    {
        if (! static::hasGuestPreletorColumn()) {
            return null;
        }

        $name = trim((string) $this->guest_preletor_name);
        if ($name === '' || !$area) {
            return null;
        }

        $normalized = Str::of($area->name)->lower()->ascii()->value();
        if (! str_contains($normalized, 'preletor') && ! str_contains($normalized, 'pregador')) {
            return null;
        }

        return $name;
    }
}
