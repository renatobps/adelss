<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationPayment;
use App\Services\EventRegistrationReceiptService;
use App\Services\Payments\MercadoPagoService;
use App\Services\WhatsAppService;
use Illuminate\Database\QueryException;
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

        // Segunda barreira contra duplo clique: o bloqueio do botão no navegador não
        // protege requisições diretas nem falhas de JavaScript.
        if ($duplicateResponse = $this->respondToExistingRegistration($event, $validated['email'] ?? null, $validated['phone'] ?? null)) {
            return $duplicateResponse;
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
                // Requisições simultâneas: o índice único barrou a segunda antes do PHP.
                if ($this->isDuplicateRegistrationError($e)) {
                    $response = $this->respondToExistingRegistration($event, $validated['email'] ?? null, $validated['phone'] ?? null, true);
                    if ($response) {
                        return $response;
                    }
                }

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

            $this->ensureReceiptCredentials($registration);
            // Pagamento ainda pendente: não há comprovante para emitir, então a
            // mensagem ao inscrito sai por aqui. O PDF vai depois da aprovação.
            $this->sendWhatsAppNotifications($event, $registration, true);

            return back()
                ->with('success', $this->successMessage($event, $registration))
                ->with('pix_payment', $this->pixPaymentPayload($paymentRecord));
        }

        try {
            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'custom_answers' => $customAnswers ?: null,
                'status' => EventRegistration::STATUS_PENDENTE,
            ]);
        } catch (\Throwable $e) {
            if (! $this->isDuplicateRegistrationError($e)) {
                throw $e;
            }
            $response = $this->respondToExistingRegistration($event, $validated['email'] ?? null, $validated['phone'] ?? null, true);
            if (! $response) {
                throw $e;
            }

            return $response;
        }

        $this->ensureReceiptCredentials($registration);

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

        // Inscrição já completa: o inscrito recebe uma única mensagem, montada
        // pelo comprovante a partir do texto configurado no evento. Aqui só
        // avisamos o responsável.
        $this->sendWhatsAppNotifications($event, $registration, false);

        try {
            app(EventRegistrationReceiptService::class)->enviarComprovante($registration);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar comprovante de inscrição (evento gratuito)', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', $this->successMessage($event, $registration));
    }

    /**
     * Reenvia o comprovante de uma inscrição já existente, a pedido de quem tentou
     * se inscrever de novo. Não cria nem altera registros.
     */
    public function resendReceipt(Request $request, string $slug)
    {
        $event = Event::query()
            ->where('public_slug', $slug)
            ->where('visibility', 'public')
            ->firstOrFail();

        $validated = $request->validate([
            'contato' => ['required', 'string', 'max:255'],
        ]);

        $registration = $this->findExistingRegistration(
            $event,
            filter_var($validated['contato'], FILTER_VALIDATE_EMAIL) ? $validated['contato'] : null,
            filter_var($validated['contato'], FILTER_VALIDATE_EMAIL) ? null : $validated['contato']
        );

        if (! $registration) {
            return back()->with('error', 'Não encontramos uma inscrição com esse contato neste evento.');
        }

        if (empty($registration->phone)) {
            return back()->with('error', 'Sua inscrição não tem telefone cadastrado — fale com a organização do evento.');
        }

        $resultado = app(EventRegistrationReceiptService::class)->enviarComprovante($registration);

        return $resultado['success'] ?? false
            ? back()->with('success', 'Comprovante reenviado por WhatsApp para o telefone cadastrado.')
            : back()->with('error', 'Não foi possível reenviar o comprovante agora. Tente novamente em alguns minutos.');
    }

    /**
     * Inscrição já existente no evento para o mesmo e-mail ou telefone.
     */
    private function findExistingRegistration(Event $event, ?string $email, ?string $phone): ?EventRegistration
    {
        $email = trim((string) $email);
        $phone = trim((string) $phone);

        if ($email === '' && $phone === '') {
            return null;
        }

        return EventRegistration::query()
            ->where('event_id', $event->id)
            ->where(function ($q) use ($email, $phone) {
                if ($email !== '') {
                    $q->orWhereRaw('LOWER(email) = ?', [mb_strtolower($email)]);
                }
                if ($phone !== '') {
                    $q->orWhere('phone', $phone);
                }
            })
            ->with('payment')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Decide o que fazer quando o contato já tem inscrição no evento.
     *
     * Dentro da janela de duplo envio a pessoa recebe a mesma mensagem de sucesso —
     * ela clicou duas vezes e não precisa saber disso. Passada a janela, é uma
     * tentativa consciente de se inscrever de novo: avisamos e oferecemos o reenvio
     * do comprovante em vez de criar um registro paralelo.
     */
    private function respondToExistingRegistration(Event $event, ?string $email, ?string $phone, bool $forceDoubleSubmit = false)
    {
        $existing = $this->findExistingRegistration($event, $email, $phone);
        if (! $existing) {
            return null;
        }

        $window = now()->subMinutes(EventRegistration::DOUBLE_SUBMIT_WINDOW_MINUTES);
        $isDoubleSubmit = $forceDoubleSubmit || ($existing->created_at && $existing->created_at->greaterThan($window));

        if ($isDoubleSubmit) {
            $redirect = back()->with('success', $this->successMessage($event, $existing));

            if ($event->is_paid && $existing->payment) {
                $redirect->with('pix_payment', $this->pixPaymentPayload($existing->payment));
            }

            return $redirect;
        }

        $redirect = back()->with('duplicate_registration', [
            'numero' => $existing->registration_number ?: '-',
            'criada_em' => $existing->created_at?->format('d/m/Y \à\s H:i'),
            'contato' => $existing->email ?: $existing->phone,
            'tem_telefone' => ! empty($existing->phone),
            'pagamento_pendente' => $event->is_paid && ! $existing->isPaymentApproved(),
        ]);

        // Em evento pago com pagamento em aberto, bloquear sem mais nada deixaria a
        // pessoa sem caminho: devolvemos o PIX da inscrição que ela já tem.
        if ($event->is_paid && $existing->payment && ! $existing->isPaymentApproved()) {
            $redirect->with('pix_payment', $this->pixPaymentPayload($existing->payment));
        }

        return $redirect;
    }

    private function successMessage(Event $event, EventRegistration $registration): string
    {
        $numero = $registration->registration_number ?: '-';

        return $event->is_paid
            ? 'Inscrição recebida! Número de inscrição: '.$numero.'. Conclua o pagamento do ingresso para confirmar sua vaga.'
            : 'Inscrição realizada com sucesso! Número de inscrição: '.$numero.'.';
    }

    /**
     * @return array<string, mixed>
     */
    private function pixPaymentPayload(EventRegistrationPayment $payment): array
    {
        return [
            'qr_code_base64' => $payment->qr_code_base64,
            'qr_code_text' => $payment->qr_code_text,
            'amount' => number_format((float) $payment->amount, 2, ',', '.'),
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
        ];
    }

    /**
     * Violação dos índices únicos (event_id + e-mail / telefone) criados como rede
     * de segurança contra requisições simultâneas.
     */
    private function isDuplicateRegistrationError(\Throwable $e): bool
    {
        while ($e !== null) {
            if ($e instanceof QueryException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
                return true;
            }
            if (str_contains(mb_strtolower($e->getMessage()), 'event_registrations_event_email_unique')
                || str_contains(mb_strtolower($e->getMessage()), 'event_registrations_event_phone_unique')) {
                return true;
            }
            $e = $e->getPrevious();
        }

        return false;
    }

    private function ensureReceiptCredentials(EventRegistration $registration): void
    {
        try {
            app(EventRegistrationReceiptService::class)->ensureCredentials($registration);
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar número/token da inscrição', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWhatsAppNotifications(Event $event, EventRegistration $registration, bool $includeRegistrantMessage): void
    {
        try {
            $service = app(WhatsAppService::class);

            if (! $service->isConfigurado()) {
                return;
            }

            if ($includeRegistrantMessage && ! empty($registration->phone)) {
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
            return EventRegistrationReceiptService::aplicarVariaveis(
                $event->registration_success_message,
                $registration
            );
        }

        $paymentLine = $event->is_paid ? "Ingresso: R$ ".number_format((float) ($event->price ?? 0), 2, ',', '.')."\n" : '';

        return "Olá, {$registration->name}! Sua inscrição no evento \"{$event->title}\" foi recebida com sucesso.\n"
            ."Data: ".$event->start_date->format('d/m/Y H:i')."\n"
            .$paymentLine
            ."Status: pendente de pagamento\n"
            ."Nos vemos lá!";
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
