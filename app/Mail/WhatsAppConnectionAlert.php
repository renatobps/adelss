<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WhatsAppConnectionAlert extends Mailable
{
    use Queueable, SerializesModels;

    public const TYPE_DOWN = 'down';
    public const TYPE_RECOVERED = 'recovered';

    public function __construct(
        public readonly string $type,
        public readonly string $instanceName,
        public readonly \DateTimeInterface $detectedAt,
        public readonly ?string $downtime = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->type === self::TYPE_DOWN
            ? '⚠️ ADELSS: WhatsApp desconectado'
            : '✅ ADELSS: WhatsApp reconectado';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.whatsapp-connection-alert',
        );
    }
}
