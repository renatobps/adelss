<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceReportSetting extends Model
{
    protected $fillable = [
        'mode',
        'consecutive_absences_alert_threshold',
        'visitor_no_return_days_alert',
        'auto_register_visitor_as_member',
        'enable_children_count',
        'enable_volunteers_count',
        'enable_payment_method_breakdown',
        'enable_spiritual_decisions',
        'custom_fields',
    ];

    protected $casts = [
        'auto_register_visitor_as_member' => 'boolean',
        'enable_children_count' => 'boolean',
        'enable_volunteers_count' => 'boolean',
        'enable_payment_method_breakdown' => 'boolean',
        'enable_spiritual_decisions' => 'boolean',
        'custom_fields' => 'array',
        'consecutive_absences_alert_threshold' => 'integer',
        'visitor_no_return_days_alert' => 'integer',
    ];

    public static function defaultAttributes(): array
    {
        return [
            'mode' => 'detalhado',
            'consecutive_absences_alert_threshold' => 3,
            'visitor_no_return_days_alert' => 14,
            'auto_register_visitor_as_member' => false,
            'enable_children_count' => false,
            'enable_volunteers_count' => false,
            'enable_payment_method_breakdown' => false,
            'enable_spiritual_decisions' => false,
            'custom_fields' => null,
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], static::defaultAttributes());
    }

    public function isDetailed(): bool
    {
        return $this->mode === 'detalhado';
    }
}
