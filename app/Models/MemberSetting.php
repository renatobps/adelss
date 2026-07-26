<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MemberSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return Cache::remember('member_setting.' . $key, 300, function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();

            return $row?->value ?? $default;
        });
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('member_setting.' . $key);
    }

    public static function generateShortToken(int $length = 10): string
    {
        $length = max(8, min(12, $length));

        do {
            $token = Str::random($length);
            $exists = static::query()
                ->where('key', 'public_registration_token')
                ->where('value', $token)
                ->exists();
        } while ($exists);

        return $token;
    }

    public static function publicRegistrationToken(): string
    {
        $token = static::getValue('public_registration_token');
        // Tokens longos antigos (48 chars) são encurtados automaticamente
        if (!$token || strlen($token) > 12) {
            $token = static::generateShortToken(10);
            static::setValue('public_registration_token', $token);
        }

        return $token;
    }

    public static function publicRegistrationEnabled(): bool
    {
        return static::getValue('public_registration_enabled', '1') === '1';
    }
}
