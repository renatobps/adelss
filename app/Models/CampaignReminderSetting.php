<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuração dos lembretes automáticos de parcelas em atraso.
 *
 * A linha com `campaign_id` nulo é o padrão global do sistema; cada campanha
 * pode ter a sua, que só é aplicada quando `use_global` é falso.
 */
class CampaignReminderSetting extends Model
{
    /** Janela de silêncio fixa: nunca enviar fora dela, independente da configuração. */
    public const QUIET_HOUR_START = 8;
    public const QUIET_HOUR_END = 20;

    /** Intervalo aleatório entre mensagens, em segundos (evita cadência de robô). */
    public const MIN_INTERVAL_SECONDS = 8;
    public const MAX_INTERVAL_SECONDS = 20;

    public const WEEKDAYS = [
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /** Variáveis aceitas nos templates, exibidas na tela de configuração. */
    public const VARIABLES = [
        '{nome}' => 'Primeiro nome do patrocinador',
        '{nome_completo}' => 'Nome completo',
        '{campanha}' => 'Nome da campanha',
        '{parcelas_atraso}' => 'Quantidade de parcelas vencidas',
        '{valor_atraso}' => 'Valor total em aberto',
        '{vencimento}' => 'Vencimento da parcela mais antiga em aberto',
        '{dias_atraso}' => 'Dias desde o vencimento mais antigo',
        '{total_pago}' => 'Quanto já contribuiu',
        '{total_restante}' => 'Quanto falta para quitar',
        '{parcelas_pagas}' => 'Parcelas pagas (ex: 2/4)',
    ];

    protected $fillable = [
        'campaign_id',
        'use_global',
        'enabled',
        'paused',
        'days_between',
        'max_reminders',
        'send_time',
        'send_days',
        'daily_limit',
        'attach_pdf',
        'attach_pdf_first_only',
        'template_1',
        'template_2',
        'template_3',
        'tier_2_days',
        'tier_3_days',
        'courtesy_enabled',
        'courtesy_days_before',
        'courtesy_template',
    ];

    protected $casts = [
        'use_global' => 'boolean',
        'enabled' => 'boolean',
        'paused' => 'boolean',
        'attach_pdf' => 'boolean',
        'attach_pdf_first_only' => 'boolean',
        'courtesy_enabled' => 'boolean',
        'send_days' => 'array',
        'days_between' => 'integer',
        'max_reminders' => 'integer',
        'daily_limit' => 'integer',
        'tier_2_days' => 'integer',
        'tier_3_days' => 'integer',
        'courtesy_days_before' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public static function defaults(): array
    {
        return [
            'use_global' => false,
            'enabled' => false,
            'paused' => false,
            'days_between' => 7,
            'max_reminders' => 3,
            'send_time' => '10:00',
            // Domingo fica de fora: é dia de culto, a mensagem se perde e soa inoportuna.
            'send_days' => [1, 2, 3, 4, 5, 6],
            'daily_limit' => 50,
            'attach_pdf' => true,
            'attach_pdf_first_only' => true,
            'template_1' => self::defaultTemplate(1),
            'template_2' => self::defaultTemplate(2),
            'template_3' => self::defaultTemplate(3),
            'tier_2_days' => 15,
            'tier_3_days' => 30,
            'courtesy_enabled' => false,
            'courtesy_days_before' => 3,
            'courtesy_template' => self::defaultCourtesyTemplate(),
        ];
    }

    /** Configuração global do sistema, criada na primeira consulta. */
    public static function globalSettings(): self
    {
        return static::firstOrCreate(['campaign_id' => null], static::defaults());
    }

    /**
     * Configuração efetiva da campanha: a própria, se ela sobrescreve o global,
     * ou o global com a pausa local preservada. A instância devolvida pode não
     * estar persistida — serve para leitura.
     */
    public static function effectiveFor(Campaign $campaign): self
    {
        $global = static::globalSettings();
        $own = static::where('campaign_id', $campaign->id)->first();

        if ($own && ! $own->use_global) {
            return $own;
        }

        $effective = $global->replicate(['campaign_id']);
        $effective->campaign_id = $campaign->id;
        $effective->use_global = true;
        $effective->paused = (bool) ($own?->paused ?? false);
        $effective->exists = false;

        return $effective;
    }

    public function isActiveFor(Campaign $campaign): bool
    {
        return $this->enabled && ! $this->paused && $campaign->status === Campaign::STATUS_ATIVA;
    }

    public function sendHour(): int
    {
        return (int) explode(':', (string) ($this->send_time ?: '10:00'))[0];
    }

    public function sendMinute(): int
    {
        return (int) (explode(':', (string) ($this->send_time ?: '10:00'))[1] ?? 0);
    }

    public function weekdays(): array
    {
        $days = $this->send_days;

        return is_array($days) && $days !== [] ? array_map('intval', $days) : [1, 2, 3, 4, 5, 6];
    }

    /** O horário configurado respeita a janela de silêncio fixa? */
    public function sendHourAllowed(): bool
    {
        $hour = $this->sendHour();

        return $hour >= self::QUIET_HOUR_START && $hour < self::QUIET_HOUR_END;
    }

    /**
     * Template aplicável ao tempo de atraso: tom mais leve no primeiro lembrete,
     * mais direto conforme o atraso cresce.
     */
    public function templateForDays(int $daysOverdue): array
    {
        if ($daysOverdue >= $this->tier_3_days && trim((string) $this->template_3) !== '') {
            return ['tier_3', $this->template_3];
        }
        if ($daysOverdue >= $this->tier_2_days && trim((string) $this->template_2) !== '') {
            return ['tier_2', $this->template_2];
        }

        return ['tier_1', $this->template_1 ?: self::defaultTemplate(1)];
    }

    public static function defaultTemplate(int $tier): string
    {
        return match ($tier) {
            2 => "Olá, {nome}!\n\n"
                . "A campanha *{campanha}* segue com *{parcelas_atraso}* parcela(s) em aberto, "
                . "totalizando *{valor_atraso}* — são {dias_atraso} dias desde o vencimento de {vencimento}.\n\n"
                . "Você já contribuiu com {total_pago} ({parcelas_pagas}) e faltam {total_restante} para concluir.\n"
                . 'Podemos combinar a melhor forma de regularizar?',
            3 => "Olá, {nome}!\n\n"
                . "Sobre a campanha *{campanha}*: constam *{parcelas_atraso}* parcela(s) em aberto "
                . "({valor_atraso}), com {dias_atraso} dias de atraso desde {vencimento}.\n\n"
                . "Pedimos a gentileza de nos retornar para regularizar ou reprogramar o valor restante "
                . '({total_restante}). Estamos à disposição para ajustar o que for necessário.',
            default => "Olá, {nome}! 🙏\n\n"
                . "Passando para lembrar da campanha *{campanha}*: constam *{parcelas_atraso}* parcela(s) "
                . "em aberto, no total de *{valor_atraso}*.\n"
                . "Vencimento mais antigo: {vencimento}.\n\n"
                . 'Se você já efetuou o pagamento, por favor desconsidere esta mensagem. '
                . 'Qualquer dúvida, estamos à disposição!',
        };
    }

    public static function defaultCourtesyTemplate(): string
    {
        return "Olá, {nome}! 😊\n\n"
            . "Apenas um aviso: sua próxima parcela da campanha *{campanha}* vence em {vencimento}.\n\n"
            . 'Esta mensagem não é uma cobrança — é só para ajudar a lembrar. Deus abençoe!';
    }
}
