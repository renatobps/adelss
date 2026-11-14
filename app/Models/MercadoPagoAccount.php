<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoAccount extends Model
{
    protected $fillable = ['name', 'uuid', 'public_key', 'access_token', 'webhook_secret', 'statement_descriptor', 'currency', 'notification_url', 'sandbox', 'merchant_id', 'merchant_name', 'is_active', 'previous_id', 'created_by'];
    protected $hidden = ['access_token', 'webhook_secret'];
    protected $casts = ['access_token' => 'encrypted', 'webhook_secret' => 'encrypted', 'sandbox' => 'boolean', 'is_active' => 'boolean'];

    public function paymentConfig(): array
    {
        return [
            'public_key' => $this->public_key,
            'access_token' => $this->access_token,
            'webhook_secret' => $this->webhook_secret,
            'statement_descriptor' => $this->statement_descriptor,
            'currency' => $this->currency,
            'notification_url' => $this->notification_url,
            'sandbox' => $this->sandbox,
        ];
    }
}
