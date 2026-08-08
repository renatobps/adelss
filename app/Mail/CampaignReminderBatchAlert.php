<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa o administrador quando um lote de lembretes não pôde ser concluído.
 * Usa o mesmo canal (e-mail) do monitor de conexão — nunca WhatsApp, que é
 * justamente o que pode estar fora do ar.
 */
class CampaignReminderBatchAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $campaignName,
        public readonly string $reason,
        public readonly int $sent,
        public readonly int $failed,
        public readonly \DateTimeInterface $detectedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '⚠️ ADELSS: lembretes da campanha ' . $this->campaignName . ' interrompidos');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.campaign-reminder-batch-alert');
    }
}
