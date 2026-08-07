<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRegistration;
use App\Models\EventRegistrationField;
use App\Models\EventScheduleItem;
use App\Models\EventSpeaker;
use App\Services\EventRegistrationReceiptService;
use App\Services\WhatsAppService;
use App\Support\PdfText;
use App\Support\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventosController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Event::class);
        $events = Event::query()
            ->apenasEventosGerais()
            ->with('category')
            ->orderByDesc('start_date')
            ->paginate(12);

        return view('agenda.eventos.index', compact('events'));
    }

    public function create()
    {
        $this->authorize('create', Event::class);
        $categories = EventCategory::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (EventCategory $category) => !preg_match('/culto|pgi/i', $category->name));

        return view('agenda.eventos.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Event::class);
        try {
            $data = $this->validateEvent($request);

            return DB::transaction(function () use ($request, $data) {
                $event = new Event($data['base']);
                $event->public_slug = $this->uniqueSlugFromTitle($data['base']['title']);
                $event->status = 'agendado';
                $event->recurrence = null;
                $event->visibility = 'public';

                if (empty($event->category_id)) {
                    $event->category_id = EventCategory::query()
                        ->whereRaw('LOWER(name) = ?', ['eventos'])
                        ->value('id');
                }

                if ($request->hasFile('banner_image')) {
                    $event->banner_image = $request->file('banner_image')->store('events/banners', 'public');
                }

                $event->location_photos = $this->storeLocationPhotos($request);
                $event->save();

                $this->syncSchedules($event, $request, $request->input('schedules', []));
                $this->syncRegistrationFields($event, $request->input('registration_fields', []));
                $this->syncSpeakers($event, $request);

                return redirect()
                    ->route('agenda.eventos.index')
                    ->with('success', 'Evento criado com sucesso. Link público: '.$this->publicUrl($event));
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Não foi possível salvar o evento: '.$e->getMessage());
        }
    }

    public function edit(Event $event)
    {
        $this->authorize('update', $event);
        $event->load(['scheduleItems', 'registrationFields', 'speakers']);
        $categories = EventCategory::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (EventCategory $category) => !preg_match('/culto|pgi/i', $category->name));

        return view('agenda.eventos.edit', [
            'event' => $event,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        try {
            $data = $this->validateEvent($request);

            DB::transaction(function () use ($request, $data, $event) {
                $event->fill($data['base']);

                if ($request->hasFile('banner_image')) {
                    if ($event->banner_image) {
                        Storage::disk('public')->delete($event->banner_image);
                    }
                    $event->banner_image = $request->file('banner_image')->store('events/banners', 'public');
                }

                $event->location_photos = $this->storeLocationPhotos($request, $event->location_photos ?? []);
                $event->save();

                $event->registrationFields()->delete();

                $this->syncSchedules($event, $request, $request->input('schedules', []), true);
                $this->syncRegistrationFields($event, $request->input('registration_fields', []));
                $this->syncSpeakers($event, $request, true);
            });

            return redirect()
                ->route('agenda.eventos.index')
                ->with('success', 'Evento atualizado com sucesso.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Não foi possível atualizar o evento: '.$e->getMessage());
        }
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);
        DB::transaction(function () use ($event) {
            $event->load(['speakers', 'scheduleItems']);
            if ($event->banner_image) {
                Storage::disk('public')->delete($event->banner_image);
            }
            if (is_array($event->location_photos)) {
                foreach ($event->location_photos as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            foreach ($event->speakers as $speaker) {
                if ($speaker->photo_path) {
                    Storage::disk('public')->delete($speaker->photo_path);
                }
            }
            foreach ($event->scheduleItems as $schedule) {
                if ($schedule->responsible_photo_path) {
                    Storage::disk('public')->delete($schedule->responsible_photo_path);
                }
            }
            $event->delete();
        });

        return redirect()->route('agenda.eventos.index')->with('success', 'Evento removido.');
    }

    public function duplicate(Event $event)
    {
        $this->authorize('duplicate', $event);
        $copy = DB::transaction(function () use ($event) {
            $event->load(['scheduleItems', 'registrationFields', 'speakers']);

            $new = $event->replicate();
            $new->title = $event->title.' (cópia)';
            $new->public_slug = $this->uniqueSlugFromTitle($new->title);
            $new->status = 'agendado';
            $new->banner_image = null;
            $new->location_photos = null;
            $new->save();

            foreach ($event->scheduleItems as $row) {
                $r = $row->replicate();
                $r->event_id = $new->id;
                $r->responsible_photo_path = null;
                $r->save();
            }

            foreach ($event->registrationFields as $row) {
                $r = $row->replicate();
                $r->event_id = $new->id;
                $r->save();
            }

            foreach ($event->speakers as $row) {
                $r = $row->replicate();
                $r->event_id = $new->id;
                $r->photo_path = null;
                $r->save();
            }

            return $new;
        });

        return redirect()
            ->route('agenda.eventos.edit', $copy)
            ->with('success', 'Evento duplicado. Revise e publique.');
    }

    public function registrations(Request $request, Event $event)
    {
        $this->authorize('manageRegistrations', $event);
        $event = Event::query()
            ->apenasEventosGerais()
            ->whereKey($event->id)
            ->with('category')
            ->firstOrFail();

        $registrations = $event->registrations()
            ->with('payment')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $user = $request->user();
        $canEditRegistrations = $user && ($user->is_admin
            || $user->hasPermission('agenda.events.edit')
            || $user->hasPermission('agenda.events.manage'));

        return view('agenda.eventos.registrations', compact('event', 'registrations', 'canEditRegistrations'));
    }

    public function updateRegistrationStatus(Request $request, Event $event, EventRegistration $registration)
    {
        $this->authorize('manageRegistrations', $event);
        $event = Event::query()
            ->apenasEventosGerais()
            ->whereKey($event->id)
            ->firstOrFail();

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pendente,confirmado,cancelado'],
        ]);

        $previousStatus = $registration->status;
        $registration->update(['status' => $validated['status']]);

        $notice = 'Status da inscrição atualizado.';

        // Ao confirmar manualmente, envia o comprovante (falha não reverte o status).
        if ($validated['status'] === EventRegistration::STATUS_CONFIRMADO
            && $previousStatus !== EventRegistration::STATUS_CONFIRMADO) {
            $resultado = app(EventRegistrationReceiptService::class)->enviarComprovante($registration);
            if ($resultado['success'] ?? false) {
                $notice .= ' Comprovante enviado por WhatsApp.';
            } elseif ($resultado['skipped'] ?? false) {
                $notice .= ' Comprovante não enviado — inscrito sem telefone cadastrado.';
            } else {
                $notice .= ' Falha ao enviar o comprovante por WhatsApp — use "Reenviar comprovante".';
            }
        }

        return back()->with('success', $notice);
    }

    public function resendRegistrationReceipt(Event $event, EventRegistration $registration, EventRegistrationReceiptService $receiptService)
    {
        $this->authorize('manageRegistrations', $event);

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $resultado = $receiptService->enviarComprovante($registration);

        if ($resultado['success'] ?? false) {
            return back()->with('success', 'Comprovante enviado por WhatsApp para ' . $registration->name . '.');
        }

        if ($resultado['skipped'] ?? false) {
            return back()->with('error', 'Comprovante não enviado — inscrito sem telefone cadastrado.');
        }

        return back()->with('error', 'Falha ao enviar o comprovante: ' . ($resultado['error'] ?? 'erro desconhecido'));
    }

    public function registrationReceiptPdf(Event $event, EventRegistration $registration, EventRegistrationReceiptService $receiptService)
    {
        $this->authorize('manageRegistrations', $event);

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $pdf = Pdf::loadView('agenda.eventos.pdf.comprovante', $receiptService->pdfViewData($registration))
            ->setPaper('a4');

        $fileName = 'comprovante-' . Str::slug((string) ($registration->registration_number ?: 'inscricao-' . $registration->id)) . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * QR Code da página pública do evento (PNG ou SVG).
     */
    public function qrcode(Request $request, Event $event)
    {
        $this->authorize('view', $event);
        abort_unless($event->public_slug, 404, 'Este evento não possui página pública.');

        $url = $this->publicUrl($event);
        $slug = Str::slug($event->title) ?: 'evento';

        if ($request->query('format') === 'svg') {
            return response(QrCode::svg($url), 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'attachment; filename="qrcode-' . $slug . '.svg"',
            ]);
        }

        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response(QrCode::png($url, 10), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => $disposition . '; filename="qrcode-' . $slug . '.png"',
        ]);
    }

    /**
     * Cartaz de divulgação em PDF (A4) com banner, dados do evento e QR Code.
     */
    public function cartaz(Event $event)
    {
        $this->authorize('view', $event);
        abort_unless($event->public_slug, 404, 'Este evento não possui página pública.');

        $url = $this->publicUrl($event);

        $pdf = Pdf::loadView('agenda.eventos.pdf.cartaz', [
            'event' => $event,
            'eventTitle' => PdfText::stripEmoji((string) $event->title),
            'bannerDataUri' => EventRegistrationReceiptService::publicFileDataUri($event->banner_image),
            'qrDataUri' => QrCode::pngDataUri($url, 10),
            'publicUrl' => $url,
        ])->setPaper('a4');

        return $pdf->download('cartaz-' . (Str::slug($event->title) ?: 'evento') . '.pdf');
    }

    /**
     * Tela de check-in por QR Code na entrada do evento.
     */
    public function checkIn(Event $event)
    {
        $this->authorize('manageRegistrations', $event);

        [$present, $total] = $this->checkInCounters($event);

        return view('agenda.eventos.check-in', compact('event', 'present', 'total'));
    }

    /**
     * Valida um token de check-in e registra a presença (JSON).
     */
    public function checkInValidate(Request $request, Event $event)
    {
        $this->authorize('manageRegistrations', $event);

        $token = trim((string) $request->input('token'));

        $respond = function (string $result, string $message, ?EventRegistration $registration = null) use ($event) {
            [$present, $total] = $this->checkInCounters($event);

            return response()->json([
                'result' => $result,
                'message' => $message,
                'name' => $registration?->name,
                'registration_number' => $registration?->registration_number,
                'present' => $present,
                'total' => $total,
            ]);
        };

        if ($token === '') {
            return $respond('not_found', 'Inscrição não encontrada.');
        }

        $registration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('check_in_token', $token)
            ->with('payment')
            ->first();

        if (!$registration) {
            return $respond('not_found', 'Inscrição não encontrada.');
        }

        if ($registration->status === EventRegistration::STATUS_CANCELADO) {
            return $respond('cancelled', 'Inscrição cancelada.', $registration);
        }

        if ($registration->checked_in_at) {
            return $respond('already', 'Já utilizado às ' . $registration->checked_in_at->format('H:i') . '.', $registration);
        }

        $paymentPending = $event->is_paid && !$registration->isPaymentApproved();
        if ($paymentPending && !$request->boolean('force')) {
            return $respond('pending', 'Pagamento pendente.', $registration);
        }

        $registration->update([
            'checked_in_at' => now(),
            'checked_in_by' => $request->user()?->id,
        ]);

        return $respond('ok', 'Entrada liberada.', $registration);
    }

    /**
     * @return array{0: int, 1: int} [presentes, total de inscrições válidas]
     */
    private function checkInCounters(Event $event): array
    {
        $base = $event->registrations()
            ->whereIn('status', [EventRegistration::STATUS_PENDENTE, EventRegistration::STATUS_CONFIRMADO]);

        return [
            (clone $base)->whereNotNull('checked_in_at')->count(),
            $base->count(),
        ];
    }

    public function sendPixToRegistrationWhatsapp(Event $event, EventRegistration $registration, WhatsAppService $whatsAppService)
    {
        $this->authorize('manageRegistrations', $event);
        $event = Event::query()
            ->apenasEventosGerais()
            ->whereKey($event->id)
            ->firstOrFail();

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $registration->load('payment');
        $payment = $registration->payment;

        if (!$payment) {
            return back()->with('error', 'Não há pagamento vinculado a esta inscrição.');
        }

        $paymentMethod = strtolower((string) ($payment->payment_method ?? ''));
        if ($paymentMethod !== 'pix') {
            return back()->with('error', 'Somente pagamentos PIX possuem QR Code/copia e cola para envio.');
        }

        if (empty($registration->phone)) {
            return back()->with('error', 'Esta inscrição não possui telefone cadastrado.');
        }

        $qrCodeText = trim((string) ($payment->qr_code_text ?? ''));
        if ($qrCodeText === '') {
            return back()->with('error', 'Código PIX não disponível para esta inscrição.');
        }
        $qrCodeBase64 = trim((string) ($payment->qr_code_base64 ?? ''));
        if ($qrCodeBase64 === '') {
            return back()->with('error', 'Imagem do QR Code PIX não disponível para esta inscrição.');
        }

        if (!$whatsAppService->isConfigurado()) {
            return back()->with('error', 'WhatsApp não configurado. Verifique em Notificações > Configuração WPP.');
        }

        $captionImagem = "QR Code PIX - {$event->title}\n"
            ."Valor: R$ ".number_format((float) ($payment->amount ?? 0), 2, ',', '.');

        $resultadoImagem = $whatsAppService->enviarImagemBase64(
            (string) $registration->phone,
            $qrCodeBase64,
            $captionImagem
        );

        if (!($resultadoImagem['success'] ?? false)) {
            $erroImagem = (string) ($resultadoImagem['error'] ?? 'Falha ao enviar imagem do QR Code no WhatsApp.');

            return back()->with('error', 'Não foi possível enviar o QR Code PIX pelo WhatsApp. '.$erroImagem);
        }

        $mensagem = "Olá, {$registration->name}!\n"
            ."Segue o PIX da inscrição do evento \"{$event->title}\".\n"
            ."Valor: R$ ".number_format((float) ($payment->amount ?? 0), 2, ',', '.')."\n\n"
            ."Copia e cola PIX:\n{$qrCodeText}";

        $resultado = $whatsAppService->enviarMensagem((string) $registration->phone, $mensagem);

        if (!($resultado['success'] ?? false)) {
            $erro = (string) ($resultado['error'] ?? 'Falha ao enviar mensagem no WhatsApp.');

            return back()->with('error', 'Não foi possível enviar o PIX pelo WhatsApp. '.$erro);
        }

        return back()->with('success', 'PIX enviado no WhatsApp com QR Code e código copia e cola.');
    }

    /**
     * Upload de imagem para o TinyMCE (campo "Sobre o evento"). Resposta no formato esperado pelo editor.
     */
    public function uploadEditorImage(Request $request)
    {
        $this->authorize('uploadEditorImage', Event::class);
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store('events/editor', 'public');
        $url = Event::publicStorageUrl($path);

        return response()->json(['location' => $url]);
    }

    /**
     * Garante HH:MM ou HH:MM:SS válidos (evita '' ?? '09:00' manter string vazia em PHP).
     */
    private function normalizeTime(?string $time, string $defaultHhMm = '09:00'): string
    {
        $time = $time !== null ? trim($time) : '';
        if ($time === '') {
            $time = $defaultHhMm;
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time.':00';
        }
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        return $defaultHhMm.':00';
    }

    private function validateEvent(Request $request): array
    {
        if ($request->input('category_id') === '' || $request->input('category_id') === null) {
            $request->merge(['category_id' => null]);
        }
        if (! $request->filled('subtitle_color')) {
            $request->merge(['subtitle_color' => null]);
        }
        if (! $request->filled('subtitle_font_family')) {
            $request->merge(['subtitle_font_family' => null]);
        }
        if (! $request->filled('page_palette')) {
            $request->merge(['page_palette' => null]);
        }

        $rules = [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'subtitle_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'subtitle_font_family' => ['nullable', Rule::in(Event::allowedSubtitleFontFamilies())],
            'page_palette' => 'nullable|in:oceano,royal,sunset,forest,grape,rose',
            'start_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_date' => 'nullable|date',
            'end_time' => 'nullable|date_format:H:i',
            'all_day' => 'nullable|boolean',
            'description' => 'nullable|string',
            'about_html' => 'nullable|string',
            'location' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:event_categories,id',
            'is_paid' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'max_spots' => 'nullable|integer|min:0',
            'phone_required' => 'nullable|boolean',
            'address_required' => 'nullable|boolean',
            'email_required' => 'nullable|boolean',
            'hide_phone' => 'nullable|boolean',
            'hide_address' => 'nullable|boolean',
            'notify_emails' => 'nullable|string',
            'responsible_name' => 'nullable|string|max:255',
            'responsible_phone' => ['nullable', 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/'],
            'registration_success_message' => 'nullable|string|max:2000',
            'registration_enabled' => 'nullable|boolean',
            'banner_image' => 'nullable|image|max:5120',
            'location_photos.*' => 'nullable|image|max:5120',
            'schedules' => 'nullable|array',
            'schedules.*.title' => 'nullable|string|max:255',
            'schedules.*.detail' => 'nullable|string|max:2000',
            'schedules.*.day' => 'nullable|integer|min:1|max:60',
            'schedules.*.hh' => 'nullable|integer|min:0|max:23',
            'schedules.*.mm' => 'nullable|integer|min:0|max:59',
            'schedules.*.responsible_name' => 'nullable|string|max:255',
            'schedules.*.responsible_photo' => 'nullable|image|max:4096',
            'schedules.*.existing_responsible_photo' => 'nullable|string|max:500',
            'registration_fields' => 'nullable|array',
            'registration_fields.*.name' => 'nullable|string|max:255',
            'registration_fields.*.field_type' => 'nullable|in:text,textarea,checkbox,radio,select',
            'registration_fields.*.required' => 'nullable|boolean',
            'registration_fields.*.options' => 'nullable|string|max:2000',
            'speakers' => 'nullable|array',
            'speakers.*.name' => 'nullable|string|max:255',
            'speakers.*.description' => 'nullable|string|max:2000',
            'speakers.*.photo' => 'nullable|image|max:4096',
            'speakers.*.existing_photo' => 'nullable|string|max:500',
        ];

        $v = $request->validate($rules);

        $allDay = $request->boolean('all_day');
        if ($allDay) {
            $start = Carbon::parse($v['start_date'].' 00:00:00');
        } else {
            $startTime = $this->normalizeTime($v['start_time'] ?? null, '09:00');
            $start = Carbon::parse(trim($v['start_date']).' '.$startTime);
        }

        $end = null;
        if (!empty($v['end_date'])) {
            if ($allDay) {
                $end = Carbon::parse($v['end_date'].' 23:59:59');
            } else {
                $endTime = $this->normalizeTime($v['end_time'] ?? null, '18:00');
                $end = Carbon::parse(trim($v['end_date']).' '.$endTime);
            }
        } else {
            $end = $start->copy()->endOfDay();
        }

        $base = [
            'title' => $v['title'],
            'subtitle' => $v['subtitle'] ?? null,
            'subtitle_color' => $v['subtitle_color'] ?? null,
            'subtitle_font_family' => $v['subtitle_font_family'] ?? null,
            'page_palette' => $v['page_palette'] ?? null,
            'description' => $v['description'] ?? null,
            'about_html' => $v['about_html'] ?? null,
            'start_date' => $start,
            'end_date' => $end,
            'all_day' => $allDay,
            'location' => $v['location'] ?? null,
            'category_id' => $v['category_id'] ?? null,
            'is_paid' => $request->boolean('is_paid'),
            'price' => $request->boolean('is_paid') ? ($v['price'] ?? null) : null,
            'max_spots' => array_key_exists('max_spots', $v) && $v['max_spots'] !== '' && $v['max_spots'] !== null
                ? (int) $v['max_spots']
                : null,
            'phone_required' => $request->boolean('phone_required'),
            'address_required' => $request->boolean('address_required'),
            'email_required' => $request->boolean('email_required'),
            'hide_phone' => $request->boolean('hide_phone'),
            'hide_address' => $request->boolean('hide_address'),
            'notify_emails' => $v['notify_emails'] ?? null,
            'responsible_name' => $v['responsible_name'] ?? null,
            'responsible_phone' => $v['responsible_phone'] ?? null,
            'registration_success_message' => $v['registration_success_message'] ?? null,
            'registration_enabled' => $request->boolean('registration_enabled'),
        ];

        return ['base' => $base];
    }

    private function uniqueSlugFromTitle(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'evento';
        }
        $slug = $base.'-'.Str::lower(Str::random(4));
        while (Event::where('public_slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    public function publicUrl(Event $event): string
    {
        return url('/evento/'.$event->public_slug);
    }

    private function storeLocationPhotos(Request $request, array $existing = []): ?array
    {
        $paths = $existing;
        if ($request->hasFile('location_photos')) {
            foreach ($request->file('location_photos') as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('events/locations', 'public');
                }
            }
        }

        return $paths ?: null;
    }

    private function syncSchedules(Event $event, Request $request, array $rows, bool $isUpdate = false): void
    {
        $oldPhotoPaths = [];
        if ($isUpdate) {
            $event->load('scheduleItems');
            $oldPhotoPaths = $event->scheduleItems->pluck('responsible_photo_path')->filter()->values()->all();
            $event->scheduleItems()->delete();
        }

        $order = 0;
        $newPhotoPaths = [];
        foreach ($rows as $i => $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $photoPath = null;
            $file = $request->file("schedules.$i.responsible_photo");
            if ($file && $file->isValid()) {
                $photoPath = $file->store('events/schedules', 'public');
            } elseif (!empty($row['existing_responsible_photo'])) {
                $photoPath = $row['existing_responsible_photo'];
            }
            if ($photoPath) {
                $newPhotoPaths[] = $photoPath;
            }

            EventScheduleItem::create([
                'event_id' => $event->id,
                'day' => max(1, (int) ($row['day'] ?? 1)),
                'title' => $title,
                'detail' => $row['detail'] ?? null,
                'responsible_name' => $row['responsible_name'] ?? null,
                'responsible_photo_path' => $photoPath,
                'time_hh' => isset($row['hh']) && $row['hh'] !== '' ? (int) $row['hh'] : null,
                'time_mm' => isset($row['mm']) && $row['mm'] !== '' ? (int) $row['mm'] : null,
                'sort_order' => $order++,
            ]);
        }

        if ($isUpdate && $oldPhotoPaths) {
            foreach ($oldPhotoPaths as $path) {
                if ($path && !in_array($path, $newPhotoPaths, true)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    private function syncRegistrationFields(Event $event, array $rows): void
    {
        $order = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $type = $row['field_type'] ?? 'text';
            $options = null;
            if (in_array($type, ['radio', 'select'], true) && !empty($row['options'])) {
                $options = array_values(array_filter(array_map('trim', explode(',', $row['options']))));
            }
            EventRegistrationField::create([
                'event_id' => $event->id,
                'name' => $name,
                'field_type' => $type,
                'required' => !empty($row['required']),
                'options' => $options,
                'sort_order' => $order++,
            ]);
        }
    }

    private function syncSpeakers(Event $event, Request $request, bool $isUpdate = false): void
    {
        $oldPhotoPaths = [];
        if ($isUpdate) {
            $event->load('speakers');
            $oldPhotoPaths = $event->speakers->pluck('photo_path')->filter()->values()->all();
            $event->speakers()->delete();
        }

        $rows = $request->input('speakers', []);
        $order = 0;
        $newPhotoPaths = [];
        foreach ($rows as $i => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $photoPath = null;
            $file = $request->file("speakers.$i.photo");
            if ($file && $file->isValid()) {
                $photoPath = $file->store('events/speakers', 'public');
            } elseif (!empty($row['existing_photo'])) {
                $photoPath = $row['existing_photo'];
            }
            if ($photoPath) {
                $newPhotoPaths[] = $photoPath;
            }
            EventSpeaker::create([
                'event_id' => $event->id,
                'name' => $name,
                'description' => $row['description'] ?? null,
                'photo_path' => $photoPath,
                'sort_order' => $order++,
            ]);
        }

        if ($isUpdate && $oldPhotoPaths) {
            foreach ($oldPhotoPaths as $path) {
                if ($path && !in_array($path, $newPhotoPaths, true)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }
}
