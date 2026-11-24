<?php

namespace App\Services;

use App\Models\DepartmentEvent;
use App\Models\DepartmentEventNotification;
use App\Models\DepartmentEventNotificationBatch;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentEventNotificationService
{
    public const DEFAULT_MESSAGE = "Olá, {nome}!\nLembrete do evento: {evento}\nDepartamento: {departamento}\nData: {data} · {horario}\nLocal: {local}\nEsperamos você!";

    public function __construct(private WhatsAppService $whatsapp) {}

    public static function automaticKey(DepartmentEventNotification $setting): string
    {
        return 'auto:'.$setting->event_id.':'.$setting->scheduled_at->timestamp;
    }

    public function createBatch(DepartmentEvent $event, string $key, string $mode, ?int $userId = null): DepartmentEventNotificationBatch
    {
        return DB::transaction(function () use ($event, $key, $mode, $userId) {
            $event = DepartmentEvent::whereHas('department')->lockForUpdate()->findOrFail($event->id);
            if ($existing = $event->notificationBatches()->where('request_key', $key)->first()) {
                return $existing;
            }
            $setting = $event->notification()->lockForUpdate()->first();
            if (!$setting || !$setting->instance_id || !$event->participants()->exists()) {
                throw ValidationException::withMessages(['notification' => 'Salve uma instância e selecione os participantes antes de enviar.']);
            }
            if ($mode === 'automatic' && (!$setting->automatic_enabled || !$setting->scheduled_at
                || $setting->scheduled_at->isFuture() || self::automaticKey($setting) !== $key)) {
                throw ValidationException::withMessages(['notification' => 'O agendamento foi alterado ou desativado.']);
            }
            $batch = $event->notificationBatches()->create([
                'request_key' => $key, 'mode' => $mode, 'instance_id' => $setting->instance_id,
                'instance_name' => $setting->instance_name, 'created_by' => $userId,
            ]);
            $phones = [];
            foreach ($event->participants()->orderBy('members.id')->get() as $member) {
                $phone = WhatsAppService::normalizarNumero((string) $member->phone);
                $error = null;
                if (!preg_match('/^[1-9]\d{9,14}$/D', $phone)) {
                    $error = 'Telefone ausente ou inválido no cadastro do membro.';
                } elseif (isset($phones[$phone])) {
                    $error = 'Telefone repetido neste envio; a mensagem será enviada uma única vez.';
                } else {
                    $phones[$phone] = true;
                }
                DB::table('department_event_notification_deliveries')->insert([
                    'batch_id' => $batch->id, 'member_id' => $member->id, 'recipient_name' => $member->name,
                    'phone' => $phone && strlen($phone) <= 30 ? $phone : null, 'message' => $this->messageFor($event, $member, $setting->message ?: self::DEFAULT_MESSAGE),
                    'status' => $error ? 'skipped' : 'pending', 'error' => $error,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $batch;
        });
    }

    public function enqueueDue(): int
    {
        $count = 0;
        DepartmentEventNotification::where('automatic_enabled', true)->where('scheduled_at', '<=', now())
            ->whereHas('event.department')->with('event')->chunkById(100, function ($settings) use (&$count) {
                foreach ($settings as $setting) {
                    $key = self::automaticKey($setting);
                    if (DepartmentEventNotificationBatch::where('request_key', $key)->exists()) continue;
                    try {
                        $this->createBatch($setting->event, $key, 'automatic');
                        $count++;
                    } catch (ValidationException $exception) {
                        // No participants or settings changed concurrently; wait for a valid configuration.
                    }
                }
            });

        return $count;
    }

    public function processPending(int $limit = 10, ?int $batchId = null): int
    {
        $table = DB::table('department_event_notification_deliveries');
        // An interrupted network request may already have reached WhatsApp. Never resend it automatically.
        (clone $table)->where('status', 'processing')->where('claimed_at', '<', now()->subMinutes(5))
            ->update(['status' => 'unknown', 'error' => 'Envio interrompido; confira o recebimento antes de reenviar manualmente.', 'updated_at' => now()]);
        $pending = (clone $table)->where('status', 'pending')->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
            ->orderBy('id')->limit(max(1, min($limit, 100)))->get();
        $processed = 0;
        foreach ($pending as $delivery) {
            $claimed = (clone $table)->where('id', $delivery->id)->where('status', 'pending')->update([
                'status' => 'processing', 'claimed_at' => now(), 'updated_at' => now(),
            ]);
            if (!$claimed) continue;
            $batch = DepartmentEventNotificationBatch::with(['event.department', 'event.notification'])->find($delivery->batch_id);
            $event = $batch?->event;
            $setting = $event?->notification;
            $member = $event?->participants()->where('members.id', $delivery->member_id)->first();
            $cancelled = !$event?->department || !$member;
            if ($batch?->mode === 'automatic' && (!$setting?->automatic_enabled || !$setting->scheduled_at
                || self::automaticKey($setting) !== $batch->request_key)) $cancelled = true;
            if ($cancelled) {
                (clone $table)->where('id', $delivery->id)->update([
                    'status' => 'cancelled', 'error' => 'Evento, participante ou agendamento não está mais disponível.', 'updated_at' => now(),
                ]);
                continue;
            }
            // Do not send to an obsolete phone number after a member updates their registration.
            if (WhatsAppService::normalizarNumero((string) $member->phone) !== $delivery->phone) {
                (clone $table)->where('id', $delivery->id)->update([
                    'status' => 'cancelled', 'error' => 'O telefone do participante mudou. Inicie um novo envio.', 'updated_at' => now(),
                ]);
                continue;
            }
            try {
                $result = $this->whatsapp->enviarMensagemNaInstancia($batch->instance_id, $delivery->phone, $delivery->message);
                $success = (bool) ($result['success'] ?? false);
                $attributes = ['status' => $success ? 'sent' : 'failed', 'sent_at' => $success ? now() : null,
                    'error' => $success ? null : 'A instância não confirmou o envio. Verifique a conexão e tente novamente.'];
            } catch (\Throwable $exception) {
                $attributes = ['status' => 'unknown', 'error' => 'Não foi possível confirmar a entrega. Confira o recebimento antes de reenviar.'];
            }
            (clone $table)->where('id', $delivery->id)->update($attributes + ['updated_at' => now()]);
            $processed++;
        }

        return $processed;
    }

    private function messageFor(DepartmentEvent $event, Member $member, string $template): string
    {
        return strtr($template, [
            '{nome}' => $member->name, '{evento}' => $event->title, '{departamento}' => $event->department->name,
            '{data}' => $event->start_date->format('d/m/Y'),
            '{horario}' => $event->all_day ? 'Dia inteiro' : $event->start_date->format('H:i'),
            '{local}' => $event->location ?: 'A confirmar',
        ]);
    }
}
