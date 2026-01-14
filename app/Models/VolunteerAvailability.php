<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VolunteerAvailability extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'volunteer_availability';

    protected $fillable = [
        'volunteer_id',
        'days_of_week',
        'time_start',
        'time_end',
        'unavailable_start',
        'unavailable_end',
        'notes',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'unavailable_start' => 'date',
        'unavailable_end' => 'date',
    ];

    /**
     * Relacionamento com Voluntário
     */
    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }

    // Nota: O relacionamento com eventos está no modelo Volunteer
}
