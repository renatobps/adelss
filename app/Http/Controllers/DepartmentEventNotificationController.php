<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDepartmentEventNotificationBatch;
use App\Models\Department;
use App\Models\DepartmentEvent;
use App\Models\DepartmentEventNotification;
use App\Models\Member;
use App\Services\DepartmentEventNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentEventNotificationController extends Controller
{
    public function index(Department $department)
    {
        $this->authorize('update', $department);
        $events = $department->events()->with('notification')->withCount('participants')
            ->orderByDesc('start_date')->paginate(20);

        return view('departments.notifications.index', compact('department', 'events'));
    }

    public function edit(Request $request, Department $department, DepartmentEvent $event, WhatsAppService $whatsapp)
    {
        $this->authorize('update', $department);
        $event->load('participants');
        $setting = $event->notification ?: new DepartmentEventNotification();
        $instances = collect($whatsapp->listInstances($request->boolean('refresh_instances')))->map(fn ($instance) => [
            'id' => (string) $instance['id'], 'name' => (string) $instance['name'],
            'connected' => (bool) ($instance['connected'] ?? false),
        ])->filter(fn ($instance) => $instance['id'] !== '')->values();
        $members = Member::orderBy('name')->get(['id', 'name', 'phone']);
        $history = DB::table('department_event_notification_deliveries as deliveries')
            ->join('department_event_notification_batches as batches', 'batches.id', '=', 'deliveries.batch_id')
            ->where('batches.event_id', $event->id)->orderByDesc('deliveries.id')
            ->select('deliveries.*', 'batches.mode', 'batches.instance_name')->paginate(20);
        $requestKey = (string) Str::uuid();

        return view('departments.notifications.edit', compact('department', 'event', 'setting', 'instances', 'members', 'history', 'requestKey'));
    }

    public function update(Request $request, Department $department, DepartmentEvent $event, WhatsAppService $whatsapp, DepartmentEventNotificationService $notifier)
    {
        $this->authorize('update', $department);
        $data = $request->validate([
            'participants' => 'sometimes|array|max:2000',
            'participants.*' => ['required', 'integer', 'distinct', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'instance_id' => 'nullable|string|max:191',
            'message' => 'required|string|max:4000',
            'automatic_enabled' => 'required|boolean',
            'scheduled_at' => 'nullable|date_format:Y-m-d\TH:i',
            'intent' => 'required|in:save,send',
            'request_key' => 'required|uuid',
        ]);
        $automatic = $request->boolean('automatic_enabled');
        $sending = $data['intent'] === 'send';
        $selected = array_map('intval', $data['participants'] ?? []);
        $instance = !empty($data['instance_id']) ? $whatsapp->findInstanceById($data['instance_id']) : null;
        $existing = $event->notification;
        // A disconnected saved instance can remain configured while automation is paused.
        $keepExisting = !$automatic && !$sending && $existing?->instance_id === ($data['instance_id'] ?? null);
        if ((!$instance && !$keepExisting && !empty($data['instance_id'])) || (($automatic || $sending) && !$instance)) {
            throw ValidationException::withMessages(['instance_id' => 'Selecione uma instância disponível na integração WhatsApp.']);
        }
        if (($automatic || $sending) && !$selected) {
            throw ValidationException::withMessages(['participants' => 'Selecione pelo menos um participante para receber o aviso.']);
        }
        if ($automatic) {
            if (empty($data['scheduled_at'])) throw ValidationException::withMessages(['scheduled_at' => 'Informe a data e o horário do envio automático.']);
            $schedule = \Carbon\Carbon::parse($data['scheduled_at']);
            $changed = !$existing?->automatic_enabled || !$existing->scheduled_at?->equalTo($schedule);
            if ($changed && !$schedule->isFuture()) {
                throw ValidationException::withMessages(['scheduled_at' => 'Escolha um horário futuro para ativar o envio automático.']);
            }
        }

        $batch = DB::transaction(function () use ($event, $data, $automatic, $selected, $instance, $existing, $sending, $request, $notifier) {
            DepartmentEvent::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $historical = $event->participants()->withTrashed()->whereNotNull('members.deleted_at')->pluck('members.id')->all();
            $event->participants()->sync(array_unique(array_merge($selected, $historical)));
            $event->notification()->updateOrCreate([], [
                'instance_id' => $data['instance_id'] ?? null,
                'instance_name' => $instance['name'] ?? $existing?->instance_name,
                'message' => $data['message'], 'automatic_enabled' => $automatic,
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            return $sending ? $notifier->createBatch($event, 'manual:'.$event->id.':'.$data['request_key'], 'manual', $request->user()->id) : null;
        });
        if ($batch) ProcessDepartmentEventNotificationBatch::dispatchAfterResponse($batch->id);

        return redirect()->route('departments.events.notifications.edit', [$department, $event])->with('success', $batch
            ? 'Configurações salvas. O envio foi iniciado; acompanhe o histórico abaixo.' : 'Participantes e notificações salvos com sucesso!');
    }

    public function cancel(Department $department, DepartmentEvent $event)
    {
        $this->authorize('update', $department);
        DB::transaction(function () use ($event) {
            DepartmentEvent::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $event->notification()->update(['automatic_enabled' => false]);
            DB::table('department_event_notification_deliveries')
                ->whereIn('batch_id', $event->notificationBatches()->select('id'))->where('status', 'pending')
                ->update(['status' => 'cancelled', 'error' => 'Envio cancelado pelo usuário.', 'updated_at' => now()]);
        });

        return back()->with('success', 'Agendamento desativado e envios pendentes cancelados. Mensagens já em processamento podem ser concluídas.');
    }
}
