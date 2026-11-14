<section id="inscricao" class="evx-section light">
    <div class="container">
        <div class="event-form-card">
            <h3>Inscrição</h3>
            @if($event->is_paid)
                <div class="alert alert-warning">
                    <strong>Ingresso pago:</strong> R$ {{ number_format((float) ($event->price ?? 0), 2, ',', '.') }}<br>
                    <small>A vaga é confirmada automaticamente após aprovação do pagamento.</small>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Confira os dados da inscrição:</strong>
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('duplicate_registration'))
                @php $jaInscrito = session('duplicate_registration'); @endphp
                <div class="alert alert-warning">
                    <strong>Você já está inscrito neste evento.</strong><br>
                    Inscrição nº {{ $jaInscrito['numero'] }}, feita em {{ $jaInscrito['criada_em'] }}.
                    @if($jaInscrito['pagamento_pendente'] ?? false)
                        <div class="mt-1">O pagamento continua em aberto — conclua abaixo para confirmar sua vaga.</div>
                    @endif
                    @if($jaInscrito['tem_telefone'])
                        <form method="post" action="{{ route('events.public.resend-receipt', $event->public_slug) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="contato" value="{{ $jaInscrito['contato'] }}">
                            <button type="submit" class="btn btn-sm btn-outline-dark">
                                Reenviar meu comprovante por WhatsApp
                            </button>
                        </form>
                    @else
                        <div class="mt-1"><small>Fale com a organização do evento para receber o comprovante.</small></div>
                    @endif
                </div>
            @endif
            @if(!$event->registration_enabled || $event->status !== 'agendado')
                <div class="alert alert-secondary mb-0">Inscrições encerradas para este evento.</div>
            @elseif($spotsLeft !== null && $spotsLeft === 0)
                <p class="text-muted mb-0">Vagas esgotadas.</p>
            @else
                @if($spotsLeft !== null)
                    <p class="small text-muted">Vagas restantes: {{ $spotsLeft }}</p>
                @endif
                @if(is_array($pixPayment) && (($pixPayment['payment_method'] ?? 'pix') === 'pix') && !empty($pixPayment['qr_code_text']))
                    <div class="alert alert-info">
                        <strong>Pagamento pendente</strong><br>
                        Conclua o PIX abaixo para confirmar seu ingresso.
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            @if(!empty($pixPayment['qr_code_base64']))
                                <div class="text-center mb-2">
                                    <img src="data:image/png;base64,{{ $pixPayment['qr_code_base64'] }}" alt="QR Code PIX" style="max-width:260px; width:100%;">
                                </div>
                            @endif
                            <label class="form-label">Código copia e cola</label>
                            <textarea class="form-control" rows="4" readonly>{{ $pixPayment['qr_code_text'] }}</textarea>
                        </div>
                    </div>
                @elseif(is_array($pixPayment) && (($pixPayment['payment_method'] ?? '') !== 'pix'))
                    <div class="alert alert-info">
                        <strong>Pagamento em processamento</strong><br>
                        Método: cartão. Status atual: {{ $pixPayment['status'] ?? 'pending' }}.
                    </div>
                @endif
                <form method="post" action="{{ route('events.public.register', $event->public_slug) }}" id="event-registration-form" data-event-paid="{{ $event->is_paid ? 'true' : 'false' }}">
                    @csrf
                    <input type="hidden" name="payment_account_version" value="{{ $event->mercado_pago_account_id ?: 'default' }}">
                    <div class="mb-3">
                        <label class="form-label">Nome completo *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    @if(!$event->hide_phone || $event->phone_required)
                        <div class="mb-3">
                            <label class="form-label">Telefone @if($event->phone_required)*@endif</label>
                            <input type="text" name="phone" class="form-control js-phone-mask" value="{{ old('phone') }}" placeholder="(99) 99999-9999" maxlength="15" @if($event->phone_required) required @endif>
                        </div>
                    @endif
                    @if(!$event->hide_address || $event->address_required)
                        <div class="mb-3">
                            <label class="form-label">Endereço @if($event->address_required)*@endif</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}" @if($event->address_required) required @endif>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">E-mail @if($event->email_required)*@endif</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $useConfiguredPayer ? config('mercadopago.test_payer_email') : '') }}" @if($event->email_required) required @endif @if($useConfiguredPayer) readonly @endif>
                    </div>
                    @if($event->is_paid)
                        <div class="mb-3">
                            <label class="form-label">CPF para pagamento *</label>
                            <input type="text" id="payer_document" name="payer_document" class="form-control js-cpf-mask" value="{{ old('payer_document', $useConfiguredPayer ? config('mercadopago.test_payer_document') : '') }}" placeholder="Somente números" maxlength="14" required @if($useConfiguredPayer) readonly @endif>
                            @if($useConfiguredPayer)
                                <small class="text-muted d-block mt-1">Pagador de teste configurado via `.env` (MP_TEST_PAYER_EMAIL / MP_TEST_PAYER_DOCUMENT).</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Forma de pagamento *</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input js-payment-method" type="radio" name="payment_method" id="pay_pix" value="pix" {{ old('payment_method', 'pix') === 'pix' ? 'checked' : '' }}>
                                <label class="form-check-label" for="pay_pix">PIX</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input js-payment-method" type="radio" name="payment_method" id="pay_card" value="card" {{ old('payment_method') === 'card' ? 'checked' : '' }}>
                                <label class="form-check-label" for="pay_card">Cartão</label>
                            </div>
                        </div>
                        <div id="card-payment-box" class="border rounded p-3 mb-3 d-none">
                            <p class="small text-muted mb-2">Preencha os dados do cartão para pagamento imediato.</p>
                            <div class="mb-2">
                                <label class="form-label">Nome no cartão</label>
                                <input type="text" class="form-control js-card-field" id="form-checkout__cardholderName" autocomplete="cc-name">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Número do cartão</label>
                                <input type="text" class="form-control js-card-field" id="form-checkout__cardNumber" autocomplete="cc-number">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Validade</label>
                                    <input type="text" class="form-control js-card-field" id="form-checkout__expirationDate" placeholder="MM/AA" autocomplete="cc-exp">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control js-card-field" id="form-checkout__securityCode" autocomplete="cc-csc">
                                </div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <label class="form-label">Bandeira</label>
                                    <input type="text" class="form-control" id="card_brand_display" placeholder="Preenchido automaticamente" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Banco emissor</label>
                                    <select class="form-select js-card-field" id="form-checkout__issuer"></select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Parcelas</label>
                                <select class="form-select js-card-field" id="form-checkout__installments"></select>
                            </div>
                            <select id="form-checkout__identificationType" class="d-none"></select>
                            <input type="hidden" id="form-checkout__identificationNumber">
                            <input type="hidden" id="form-checkout__cardholderEmail">
                            <input type="hidden" name="card_token" id="card_token">
                            <input type="hidden" name="card_payment_method_id" id="card_payment_method_id">
                            <input type="hidden" name="card_issuer_id" id="card_issuer_id">
                            <input type="hidden" name="card_installments" id="card_installments">
                        </div>
                    @endif

                    @foreach(($customFields ?? $event->registrationFields) as $field)
                        <div class="mb-3">
                            <label class="form-label">{{ $field->name }} @if($field->required)*@endif</label>
                            @if($field->field_type === 'textarea')
                                <textarea name="custom[{{ $field->id }}]" class="form-control" rows="3" @if($field->required) required @endif>{{ old('custom.'.$field->id) }}</textarea>
                            @elseif($field->field_type === 'checkbox')
                                <div class="form-check">
                                    <input type="checkbox" name="custom[{{ $field->id }}]" value="1" class="form-check-input" id="cf{{ $field->id }}"
                                           {{ old('custom.'.$field->id) ? 'checked' : '' }} @if($field->required) required @endif>
                                    <label class="form-check-label" for="cf{{ $field->id }}">Sim</label>
                                </div>
                            @elseif(in_array($field->field_type, ['radio', 'select'], true) && !empty($field->options))
                                @if($field->field_type === 'select')
                                    <select name="custom[{{ $field->id }}]" class="form-select" @if($field->required) required @endif>
                                        <option value="">Selecione...</option>
                                        @foreach($field->options as $opt)
                                            <option value="{{ $opt }}" @selected(old('custom.'.$field->id) == $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    @foreach($field->options as $opt)
                                        <div class="form-check">
                                            <input type="radio" name="custom[{{ $field->id }}]" value="{{ $opt }}" class="form-check-input" id="cf{{ $field->id }}_{{ $loop->index }}"
                                                   @checked(old('custom.'.$field->id) == $opt) @if($field->required) required @endif>
                                            <label class="form-check-label" for="cf{{ $field->id }}_{{ $loop->index }}">{{ $opt }}</label>
                                        </div>
                                    @endforeach
                                @endif
                            @else
                                @php
                                    $normalizedFieldName = \Illuminate\Support\Str::of($field->name)->lower()->ascii()->replace('-', ' ')->replace('_', ' ')->replaceMatches('/\s+/', ' ')->trim()->value();
                                    $isAgeField = in_array($normalizedFieldName, ['idade', 'age'], true);
                                @endphp
                                <input
                                    type="{{ $isAgeField ? 'number' : 'text' }}"
                                    name="custom[{{ $field->id }}]"
                                    class="form-control"
                                    value="{{ old('custom.'.$field->id) }}"
                                    @if($isAgeField) step="1" min="0" max="130" inputmode="numeric" @endif
                                    @if($field->required) required @endif
                                >
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" id="event-registration-submit" class="event-cta w-100 text-center">
                        <span class="js-submit-label">Enviar inscrição</span>
                        <span class="js-submit-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>
                <div id="event-registration-progress" class="d-none" role="status" aria-live="polite" aria-atomic="true" style="position:fixed;inset:0;z-index:10000;background:rgba(12,24,39,.72);padding:24px;align-items:center;justify-content:center;">
                    <div style="width:100%;max-width:440px;padding:32px;border-radius:16px;background:#fff;color:#152a41;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25);">
                        <div class="spinner-border mb-3" aria-hidden="true" style="width:42px;height:42px;color:#245ca6;"></div>
                        <strong id="event-registration-progress-title" style="display:block;font-size:22px;line-height:1.4;color:#152a41;">Enviando sua inscrição…</strong>
                        <p id="event-registration-progress-message" style="margin:16px 0 0;font-size:15px;line-height:1.7;color:#405268;">Aguarde nesta página, sem atualizar ou reenviar o formulário.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
