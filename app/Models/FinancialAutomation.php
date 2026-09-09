<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAutomation extends Model
{
    public const KEY_CONTRIBUTION_THANKS = 'contribution_thanks';
    public const KEY_TREASURERS = 'treasurers';
    public const KEY_MP_TREASURY_GROUP = 'mp_treasury_group';
    public const KEY_DUE_REMINDER = 'due_reminder';
    public const KEY_SMART_SUMMARY = 'smart_summary';
    public const MAX_TREASURERS = 5;

    protected $fillable = [
        'key',
        'name',
        'enabled',
        'settings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];

    public static function defaultMessageTemplate(): string
    {
        return implode("\n", [
            '*{igreja}*',
            '',
            'Olá, {nome}! 🙏',
            '',
            'Recebemos sua contribuição de *R$ {valor}* ({tipo}).',
            '',
            'Muito obrigado pela sua fidelidade e generosidade!',
            'Deus continue abençoando você e sua família. ✨',
        ]);
    }

    public static function defaultDueReminderMessage(): string
    {
        return implode("\n", [
            '💰 *{igreja}*',
            '',
            'Olá {tesoureiro}, lembrete de despesas a vencer em {dias} dia(s):',
            '',
            '{lista}',
            '',
            '*Total:* {total}',
        ]);
    }

    public static function defaultSettingsFor(string $key): array
    {
        return match ($key) {
            self::KEY_TREASURERS => [
                'recipients' => [],
            ],
            self::KEY_MP_TREASURY_GROUP => [
                'whatsapp_group_jid' => '',
                'whatsapp_group_name' => '',
            ],
            self::KEY_DUE_REMINDER => [
                'days_ahead' => 1,
                'send_time' => '08:00',
                'second_reminder' => false,
                'min_amount' => 0,
                'message_template' => self::defaultDueReminderMessage(),
            ],
            self::KEY_SMART_SUMMARY => [
                'frequency' => 'monthly',
                'day_of_month' => 1,
                'send_time' => '08:00',
                'compare_previous' => true,
                'opening_message' => '',
            ],
            self::KEY_CONTRIBUTION_THANKS => [
                'min_amount' => 0,
                'daily_limit' => 30,
                'window_start' => '08:00',
                'window_end' => '20:00',
                'delay_minutes' => 0,
                'message_template' => self::defaultMessageTemplate(),
                'eligible_category_ids' => [],
            ],
            default => [],
        };
    }

    public static function defaultSettings(): array
    {
        return self::defaultSettingsFor(self::KEY_CONTRIBUTION_THANKS);
    }

    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    public static function ensure(string $key, string $name, bool $enabled = false, ?array $settings = null): self
    {
        $automation = static::findByKey($key);

        if ($automation) {
            return $automation;
        }

        return static::query()->create([
            'key' => $key,
            'name' => $name,
            'enabled' => $enabled,
            'settings' => $settings ?? self::defaultSettingsFor($key),
        ]);
    }

    public static function contributionThanks(): self
    {
        return static::ensure(
            self::KEY_CONTRIBUTION_THANKS,
            'Agradecimento por contribuição',
            (bool) config('financial.whatsapp.dizimo_receipt_enabled', true)
        );
    }

    public static function treasurers(): self
    {
        return static::ensure(self::KEY_TREASURERS, 'Tesoureiros (destinatários)', true);
    }

    public static function mpTreasuryGroup(): self
    {
        return static::ensure(
            self::KEY_MP_TREASURY_GROUP,
            'Grupo WhatsApp da tesouraria (Mercado Pago)',
            true
        );
    }

    public static function dueReminder(): self
    {
        return static::ensure(
            self::KEY_DUE_REMINDER,
            'Lembrete de despesas a vencer',
            (bool) config('financial.whatsapp.due_reminder_enabled', false)
        );
    }

    public static function smartSummary(): self
    {
        return static::ensure(self::KEY_SMART_SUMMARY, 'Resumo financeiro inteligente', false);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->mergedSettings();

        return $settings[$key] ?? $default;
    }

    public function mergedSettings(): array
    {
        return array_merge(self::defaultSettingsFor($this->key), $this->settings ?? []);
    }

    /**
     * @return list<array{name: string, phone: string, member_id: int|null}>
     */
    public function treasurerRecipients(): array
    {
        $recipients = $this->setting('recipients', []);
        if (!is_array($recipients)) {
            return [];
        }

        $normalized = [];
        foreach ($recipients as $recipient) {
            if (!is_array($recipient)) {
                continue;
            }

            $phone = trim((string) ($recipient['phone'] ?? ''));
            if ($phone === '') {
                continue;
            }

            $normalized[] = [
                'name' => trim((string) ($recipient['name'] ?? '')),
                'phone' => $phone,
                'member_id' => !empty($recipient['member_id']) ? (int) $recipient['member_id'] : null,
            ];

            if (count($normalized) >= self::MAX_TREASURERS) {
                break;
            }
        }

        return $normalized;
    }

    public function whatsappGroupJid(): string
    {
        return trim((string) $this->setting('whatsapp_group_jid', ''));
    }

    public function whatsappGroupName(): string
    {
        return trim((string) $this->setting('whatsapp_group_name', ''));
    }
}
