<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'leader_id',
        'min_quantity',
        'allowed_audience',
        'parent_id',
        'sort_order',
        'whatsapp_group_jid',
        'whatsapp_group_name',
    ];

    protected $casts = [
        'status' => 'string',
        'allowed_audience' => 'string',
        'min_quantity' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Relacionamento com Líder (Responsável)
     */
    public function leader()
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    /**
     * Relacionamento muitos-para-muitos com Voluntários
     */
    public function volunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'volunteer_service_areas', 'service_area_id', 'volunteer_id')
                    ->withTimestamps();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function displayName(): string
    {
        if ($this->parent) {
            return $this->parent->name.' · '.$this->name;
        }

        return $this->name;
    }

    public function assignmentAreas()
    {
        $children = $this->relationLoaded('children')
            ? $this->children->where('status', 'ativo')->values()
            : $this->children()->active()->get();

        return $children->isNotEmpty() ? $children : collect([$this]);
    }

    public function volunteerPoolAreaIds(): array
    {
        $ids = [(int) $this->id];

        foreach ($this->assignmentAreas() as $area) {
            $ids[] = (int) $area->id;
        }

        if ($this->parent_id) {
            $ids[] = (int) $this->parent_id;
        }

        return array_values(array_unique($ids));
    }

    public function resolvedWhatsAppGroupJid(): ?string
    {
        $jid = trim((string) $this->whatsapp_group_jid);
        if ($jid !== '') {
            return $jid;
        }

        if ($this->parent) {
            return $this->parent->resolvedWhatsAppGroupJid();
        }

        if ($this->parent_id) {
            $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();

            return $parent?->resolvedWhatsAppGroupJid();
        }

        return null;
    }

    public function resolvedWhatsAppGroupName(): ?string
    {
        if (trim((string) $this->whatsapp_group_jid) !== '') {
            $name = trim((string) $this->whatsapp_group_name);

            return $name !== '' ? $name : $this->whatsapp_group_jid;
        }

        if ($this->parent) {
            return $this->parent->resolvedWhatsAppGroupName();
        }

        return null;
    }

    public function resolvedLeaderName(): ?string
    {
        $leader = $this->relationLoaded('leader') ? $this->leader : $this->leader()->first();
        $name = trim((string) ($leader->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        if ($this->parent_id) {
            $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();

            return $parent?->resolvedLeaderName();
        }

        return null;
    }

    public function isIntercession(): bool
    {
        $normalized = Str::of($this->name)->lower()->ascii()->value();

        return str_contains($normalized, 'intercess');
    }

    /**
     * Áreas que só servem nos cultos de domingo (não há escala nos cultos de quarta).
     */
    public function isSundayOnly(): bool
    {
        $normalized = Str::of($this->name)->lower()->ascii()->value();

        foreach (['sala das criancas', 'limpeza', 'zeladoria', 'zelador'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        if ($this->parent_id) {
            $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();

            return (bool) $parent?->isSundayOnly();
        }

        return false;
    }

    public function slotLabels(): array
    {
        $quantity = max(1, (int) $this->min_quantity);

        if ($this->parent_id) {
            if ($quantity === 1) {
                return [$this->name];
            }

            $labels = [];
            for ($index = 1; $index <= $quantity; $index++) {
                $labels[] = "{$this->name} {$index}";
            }

            return $labels;
        }

        $normalized = Str::of($this->name)->lower()->ascii()->value();

        if (str_contains($normalized, 'sala das criancas')) {
            $labels = ['Professor(a)'];
            for ($index = 1; $index < $quantity; $index++) {
                $labels[] = $quantity === 2 ? 'Monitor' : "Monitor {$index}";
            }

            return $labels;
        }

        if (str_contains($normalized, 'preletor') || str_contains($normalized, 'pregador')) {
            $labels = ['Preletor(a)'];
            for ($index = 2; $index <= $quantity; $index++) {
                $labels[] = "Preletor(a) {$index}";
            }

            return $labels;
        }

        if ($this->isIntercession()) {
            return self::intercessionSlotLabels($quantity);
        }

        if ($quantity === 1) {
            return ['Voluntário'];
        }

        $labels = [];
        for ($index = 1; $index <= $quantity; $index++) {
            $labels[] = "Voluntário {$index}";
        }

        return $labels;
    }

    public static function intercessionSlotLabels(int $quantity): array
    {
        $positions = ['Esquerda', 'Direita', 'Atrás'];
        $perPeriod = count($positions);
        $periods = max(1, (int) ceil($quantity / $perPeriod));
        $labels = [];

        for ($index = 0; $index < $quantity; $index++) {
            $period = intdiv($index, $perPeriod) + 1;
            $position = $positions[$index % $perPeriod];
            $prefix = $periods > 1 ? "{$period}º período · " : '';
            $labels[] = "{$prefix}{$position}";
        }

        return $labels;
    }

    public function groupedIntercessionVolunteers($volunteers): array
    {
        $labels = $this->slotLabels();
        $grouped = [];

        foreach ($volunteers->values() as $index => $volunteer) {
            $label = $labels[$index] ?? 'Intercessão';
            $period = '1º período';
            $position = $label;

            if (str_contains($label, ' · ')) {
                [$period, $position] = explode(' · ', $label, 2);
            }

            $position = trim(preg_replace('/\s+\d+$/', '', $position));
            $name = $volunteer->member->name ?? 'Sem nome';
            $grouped[$period][$position][] = $name;
        }

        return $grouped;
    }
}
