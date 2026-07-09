<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationPayment;
use App\Services\Payments\MercadoPagoService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicEventController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService
    ) {}

    public function show(string $slug)
    {
        $event = Event::query()
            ->where('public_slug', $slug)
            ->where('visibility', 'public')
            ->whereIn('status', ['agendado', 'concluido'])
            ->with(['scheduleItems', 'registrationFields', 'speakers', 'category'])
            ->withCount([
                'registrations as registrations_em_vaga_count' => function ($q) {
                    $q->whereIn('status', [
                        EventRegistration::STATUS_PENDENTE,
                        EventRegistration::STATUS_CONFIRMADO,
                    ]);
                },
            ])
            ->firstOrFail();

        $customFields = $event->registrationFields
            ->filter(fn ($field) => ! $this->isDefaultRegistrationFieldName((string) $field->name))
            ->values();

        $mercadoPagoPublicKey = (string) config('mercadopago.public_key', '');

        return view('agenda.eventos.public.show', compact('event', 'customFields', 'mercadoPagoPublicKey'));
    }

    public function register(Request $request, string $slug)
    {
        $configuredTestPayerEmail = trim((string) config('mercadopago.test_payer_email', ''));
        $configuredTestPayerDocument = preg_replace('/\D+/', '', (string) config('mercadopago.test_payer_document', '')) ?: '';

        if (!$request->filled('email') && $configuredTestPayerEmail !== '') {
            $request->merge(['email' => $configuredTestPayerEmail]);
        }
        if (!$request->filled('payer_document') && $configuredTestPayerDocument !== '') {
            $request->merge(['payer_document' => $configuredTestPayerDocument]);
        }

        if ($request->filled('payer_document')) {
            $request->merge([
                'payer_document' => preg_replace('/\D+/', '', (string) $request->input('payer_document')),
            ]);
        }

        $event = Event::query()
            ->where('public_slug', $slug)
            ->where('visibility', 'public')
            ->where('status', 'agendado')
            ->with('registrationFields')
            ->firstOrFail();

        if (!$event->registration_enabled) {
            return back()->with('error', 'As inscrições estão encerradas para este evento.');
        }

        if ($event->max_spots !== null && $event->max_spots > 0) {
            $count = $event->registrations()
                ->whereIn('status', [EventRegistration::STATUS_PENDENTE, EventRegistration::STATUS_CONFIRMADO])
                ->count();
            if ($count >= $event->max_spots) {
                return back()->with('error', 'Não há mais vagas disponíveis para este evento.');
            }
        }

        $rules = [
            'name' => 'required|string|max:255',
        ];

        if ($event->email_required) {
            $rules['email'] = 'required|email|max:255';
        } else {
            $rules['email'] = 'nullable|email|max:255';
        }

        if ($event->is_paid) {
            $rules['email'] = 'required|email|max:255';
            $rules['payer_document'] = ['required', 'regex:/^\d{11}$/'];
            $rules['payment_method'] = ['required', 'in:pix,card'];
            $rules['card_token'] = ['nullable', 'string'];
            $rules['card_payment_method_id'] = ['nullable', 'string', 'max:50'];
            $rules['card_issuer_id'] = ['nullable', 'string', 'max:40'];
            $rules['card_installments'] = ['nullable', 'integer', 'min:1', 'max:24'];
        }

        if ($event->phone_required) {
            $rules['phone'] = ['required', 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/'];
        } else {
            $rules['phone'] = ['nullable', 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/'];
        }

        if ($event->address_required) {
            $rules['address'] = 'required|string|max:500';
        } else {
            $rules['address'] = 'nullable|string|max:500';
        }

        $custom = [];
        foreach ($event->registrationFields as $field) {
            if ($this->isDefaultRegistrationFieldName((string) $field->name)) {
                continue;
            }
            $key = 'custom.'.$field->id;
            $rule = ['nullable', 'string', 'max:2000'];
            if ($this->isAgeRegistrationFieldName((string) $field->name)) {
                $rule = ['nullable', 'integer', 'min:0', 'max:130'];
            }
            if ($field->required) {
                if ($this->isAgeRegistrationFieldName((string) $field->name)) {
                    $rule = ['required', 'integer', 'min:0', 'max:130'];
                } else {
                    $rule = ['required', 'string', 'max:2000'];
                }
            }
            $rules[$key] = $rule;
        }

        $validated = $request->validate($rules);

        $customAnswers = [];
        foreach ($event->registrationFields as $field) {
            if ($this->isDefaultRegistrationFieldName((string) $field->name)) {
                continue;
            }
            $val = $request->input('custom.'.$field->id);
            if ($val !== null && $val !== '') {
                $customAnswers[$field->id] = $val;
            }
        }

        if ($event->is_paid) {
            $price = (float) ($event->price ?? 0);
            if ($price <= 0) {
                return back()->withInput()->with('error', 'Este evento está configurado como pago, mas sem valor de ingresso.');
            }

            $payerEmail = trim((string) ($validated['email'] ?? $configuredTestPayerEmail));
            $payerDocument = preg_replace('/\D+/', '', (string) ($validated['payer_document'] ?? $configuredTestPayerDocument)) ?: '';

            if ($payerEmail === '' || $payerDocument === '') {
                return back()
                    ->withInput()
                    ->with('error', 'Configure MP_TEST_PAYER_EMAIL e MP_TEST_PAYER_DOCUMENT no .env, ou informe e-mail/CPF no formulário para testar pagamentos.');
            }

            if (($validated['payment_method'] ?? 'pix') === 'card') {
                if (empty($validated['card_token']) || empty($validated['card_payment_method_id']) || empty($validated['card_installments'])) {
                    return back()
                        ->withInput()
                        ->with('error', 'Preencha os dados do cartão para concluir o pagamento.');
                }
            }

            try {
                [$registration, $paymentRecord] = DB::transaction(function () use ($event, $validated, $customAnswers, $payerEmail, $payerDocument) {
                    $registration = EventRegistration::create([
                        'event_id' => $event->id,
                        'name' => $validated['name'],
                        'email' => $validated['email'] ?? null,
                        'phone' => $validated['phone'] ?? null,
                        'address' => $validated['address'] ?? null,
                        'custom_answers' => $customAnswers ?: null,
                        'status' => EventRegistration::STATUS_PENDENTE,
                    ]);

                    $idempotencyKey = (string) Str::uuid();
                    $externalReference = sprintf('event-reg-%d-%s', $registration->id, Str::random(8));

                    $isCard = ($validated['payment_method'] ?? 'pix') === 'card';
                    if ($isCard) {
                        $paymentResponse = $this->mercadoPagoService->createCardPayment([
                            'amount' => (float) $event->price,
                            'description' => 'Ingresso: '.$event->title,
                            'token' => $validated['card_token'],
                            'payer_email' => $payerEmail,
                            'payer_document' => $payerDocument,
                            'payer_document_type' => 'CPF',
                            'payment_method_id' => $validated['card_payment_method_id'],
                            'issuer_id' => $validated['card_issuer_id'] ?? null,
                            'installments' => (int) $validated['card_installments'],
                            'external_reference' => $externalReference,
                        ], $idempotencyKey);
                    } else {
                        $paymentResponse = $this->mercadoPagoService->createPixPayment([
                            'amount' => (float) $event->price,
                            'description' => 'Ingresso: '.$event->title,
                            'payer_email' => $payerEmail,
                            'payer_document' => $payerDocument,
                            'payer_document_type' => 'CPF',
                            'external_reference' => $externalReference,
                        ], $idempotencyKey);
                    }

                    $paymentRecord = EventRegistrationPayment::create([
                        'event_registration_id' => $registration->id,
                        'idempotency_key' => $idempotencyKey,
                        'external_payment_id' => isset($paymentResponse['id']) ? (string) $paymentResponse['id'] : null,
                        'external_reference' => $externalReference,
                        'status' => (string) ($paymentResponse['status'] ?? 'pending'),
                        'status_detail' => (string) ($paymentResponse['status_detail'] ?? ''),
                        'payment_method' => (string) ($paymentResponse['payment_method_id'] ?? ($isCard ? 'card' : 'pix')),
                        'amount' => (float) $event->price,
                        'currency' => (string) ($paymentResponse['currency_id'] ?? 'BRL'),
                        'payer_email' => $payerEmail,
                        'payer_document' => $payerDocument,
                        'qr_code_base64' => data_get($paymentResponse, 'point_of_interaction.transaction_data.qr_code_base64'),
                        'qr_code_text' => data_get($paymentResponse, 'point_of_interaction.transaction_data.qr_code'),
                        'raw_payload' => $paymentResponse,
                    ]);

                    return [$registration, $paymentRecord];
                });
            } catch (\Throwable $e) {
                try {
                    Log::warning('Falha ao gerar pagamento de ingresso', [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Throwable) {
                    // Evita quebrar o fluxo se o canal de log estiver com configuração inválida.
                }

                $isCard = ($validated['payment_method'] ?? 'pix') === 'card';
                $baseMessage = $isCard
                    ? 'Não foi possível processar o pagamento com cartão.'
                    : 'Não foi possível gerar o PIX do ingresso.';
                $apiMessage = trim((string) $e->getMessage());
                $friendlyDetail = '';

                if (str_contains(Str::lower($apiMessage), 'unauthorized use of live credentials')) {
                    $friendlyDetail = ' As credenciais atuais não permitem uso nesse ambiente. Use credenciais TEST para homologação.';
                } elseif ($apiMessage !== '') {
                    $friendlyDetail = ' Detalhe: '.$apiMessage;
                }

                return back()
                    ->withInput()
                    ->with('error', $baseMessage.$friendlyDetail);
            }

            $this->sendWhatsAppNotifications($event, $registration);

            return back()
                ->with('success', 'Inscrição recebida! Conclua o pagamento do ingresso para confirmar sua vaga.')
                ->with('pix_payment', [
                    'qr_code_base64' => $paymentRecord->qr_code_base64,
                    'qr_code_text' => $paymentRecord->qr_code_text,
                    'amount' => number_format((float) $paymentRecord->amount, 2, ',', '.'),
                    'status' => $paymentRecord->status,
                    'payment_method' => $paymentRecord->payment_method,
                ]);
        }

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'custom_answers' => $customAnswers ?: null,
            'status' => EventRegistration::STATUS_PENDENTE,
        ]);

        if (!empty($event->notify_emails)) {
            $emails = array_filter(array_map('trim', explode(',', $event->notify_emails)));
            foreach ($emails as $to) {
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    try {
                        Mail::raw(
                            "Nova inscrição no evento \"{$event->title}\".\nNome: {$registration->name}\nE-mail: ".($registration->email ?? '-'),
                            function ($message) use ($to, $event) {
                                $message->to($to)->subject('Nova inscrição: '.$event->title);
                            }
                        );
                    } catch (\Throwable $e) {
                        // não interrompe o fluxo se o envio falhar
                    }
                }
            }
        }

        $this->sendWhatsAppNotifications($event, $registration);

        return back()->with('success', 'Inscrição realizada com sucesso!');
    }

    private function sendWhatsAppNotifications(Event $event, EventRegistration $registration): void
    {
        try {
            $service = app(WhatsAppService::class);

            if (! $service->isConfigurado()) {
                return;
            }

            if (! empty($registration->phone)) {
                $service->enviarMensagem(
                    $registration->phone,
                    $this->buildRegistrantMessage($event, $registration)
                );
            }

            if (! empty($event->responsible_phone)) {
                $service->enviarMensagem(
                    $event->responsible_phone,
                    $this->buildResponsibleMessage($event, $registration)
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar WhatsApp de inscrição do evento', [
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildRegistrantMessage(Event $event, EventRegistration $registration): string
    {
        if (! empty($event->registration_success_message)) {
            return $this->renderRegistrationSuccessMessage(
                $event->registration_success_message,
                $event,
                $registration
            );
        }

        $statusLabel = $event->is_paid ? 'pendente de pagamento' : EventRegistration::STATUS_PENDENTE;
        $paymentLine = $event->is_paid ? "Ingresso: R$ ".number_format((float) ($event->price ?? 0), 2, ',', '.')."\n" : '';

        return "Olá, {$registration->name}! Sua inscrição no evento \"{$event->title}\" foi recebida com sucesso.\n"
            ."Data: ".$event->start_date->format('d/m/Y H:i')."\n"
            .$paymentLine
            ."Status: ".$statusLabel."\n"
            ."Nos vemos lá!";
    }

    private function renderRegistrationSuccessMessage(string $template, Event $event, EventRegistration $registration): string
    {
        $statusLabel = $event->is_paid ? 'pendente de pagamento' : EventRegistration::STATUS_PENDENTE;
        $replacements = [
            '{{nome}}' => (string) $registration->name,
            '{{evento}}' => (string) $event->title,
            '{{data}}' => $event->start_date ? $event->start_date->format('d/m/Y H:i') : '-',
            '{{status}}' => $statusLabel,
        ];

        return strtr($template, $replacements);
    }

    private function buildResponsibleMessage(Event $event, EventRegistration $registration): string
    {
        $responsible = $event->responsible_name ?: 'Responsável';

        return "Olá, {$responsible}! Nova inscrição no evento \"{$event->title}\".\n"
            ."Nome: {$registration->name}\n"
            ."Telefone: ".($registration->phone ?: '-')."\n"
            ."E-mail: ".($registration->email ?: '-')."\n"
            ."Data da inscrição: ".$registration->created_at->format('d/m/Y H:i');
    }

    private function isDefaultRegistrationFieldName(string $name): bool
    {
        $normalized = Str::of($name)
            ->lower()
            ->ascii()
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        $reserved = [
            'nome',
            'nome completo',
            'telefone',
            'endereco',
            'e mail',
            'email',
        ];

        return in_array($normalized, $reserved, true);
    }

    private function isAgeRegistrationFieldName(string $name): bool
    {
        $normalized = Str::of($name)
            ->lower()
            ->ascii()
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return in_array($normalized, ['idade', 'age'], true);
    }
}
