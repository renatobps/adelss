<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberCustomFieldValue extends Model
{
    protected $fillable = [
        'member_id',
        'member_custom_field_id',
        'value',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(MemberCustomField::class, 'member_custom_field_id');
    }
}
