<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
