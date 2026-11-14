@if($event->is_paid && !empty($mercadoPagoPublicKey))
<script src="https://sdk.mercadopago.com/js/v2"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($errors->any() || session('success') || session('error') || session('duplicate_registration') || session('pix_payment'))
    var registrationSection = document.getElementById('inscricao');
    if (registrationSection) registrationSection.scrollIntoView({block: 'start'});
    @endif
    document.querySelectorAll('.js-phone-mask').forEach(function (input) {
        var formatPhone = function (value) {
            var digits = (value || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 2) return digits.length ? '(' + digits : '';
            if (digits.length <= 7) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
        };
        input.addEventListener('input', function () {
            input.value = formatPhone(input.value);
        });
        if (input.value) {
            input.value = formatPhone(input.value);
        }
    });

    document.querySelectorAll('.js-cpf-mask').forEach(function (input) {
        var formatCpf = function (value) {
            var digits = (value || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 3) return digits;
            if (digits.length <= 6) return digits.slice(0, 3) + '.' + digits.slice(3);
            if (digits.length <= 9) return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6);
            return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6, 9) + '-' + digits.slice(9);
        };
        input.addEventListener('input', function () {
            input.value = formatCpf(input.value);
        });
        if (input.value) {
            input.value = formatCpf(input.value);
        }
    });

    var formEl = document.getElementById('event-registration-form');
    var paymentRadios = document.querySelectorAll('.js-payment-method');
    var cardBox = document.getElementById('card-payment-box');
    var emailInput = document.getElementById('email');
    var payerDocInput = document.getElementById('payer_document');
    var hiddenCardEmail = document.getElementById('form-checkout__cardholderEmail');
    var hiddenCardDoc = document.getElementById('form-checkout__identificationNumber');
    var cardBrandDisplay = document.getElementById('card_brand_display');
    var allowNativeSubmit = false;
    var mpCardForm = null;
    var mpCardFormInitialized = false;

    var submitBtn = document.getElementById('event-registration-submit');
    var isSubmitting = false;

    var setSubmitting = function (state) {
        isSubmitting = state;
        if (!submitBtn) return;
        submitBtn.disabled = state;
        var label = submitBtn.querySelector('.js-submit-label');
        var spinner = submitBtn.querySelector('.js-submit-spinner');
        if (label) label.textContent = state ? 'Enviando...' : 'Enviar inscrição';
        if (spinner) spinner.classList.toggle('d-none', !state);
    };

    if (formEl) {
        // Primeira barreira contra inscrição duplicada: o segundo clique não vira
        // uma segunda requisição. Registrado antes do Mercado Pago para vir primeiro.
        formEl.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            setSubmitting(true);
        });
    }

    // Voltar pelo histórico pode restaurar a página com o botão travado.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            setSubmitting(false);
        }
    });

    var syncCardPayerData = function () {
        if (hiddenCardEmail && emailInput) {
            hiddenCardEmail.value = emailInput.value || '';
        }
        if (hiddenCardDoc && payerDocInput) {
            hiddenCardDoc.value = (payerDocInput.value || '').replace(/\D/g, '');
        }
    };

    var getSelectedPaymentMethod = function () {
        var checked = document.querySelector('.js-payment-method:checked');
        return checked ? checked.value : 'pix';
    };

    var toggleCardBox = function () {
        if (!cardBox) return;
        var isCard = getSelectedPaymentMethod() === 'card';
        cardBox.classList.toggle('d-none', !isCard);
        cardBox.querySelectorAll('.js-card-field').forEach(function (field) {
            if (isCard) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        });

        // Em PIX, desmonta o cardForm para não interceptar submit com validações de cartão.
        if (!isCard && mpCardForm && typeof mpCardForm.unmount === 'function') {
            try {
                mpCardForm.unmount();
            } catch (e) {
                console.warn('Não foi possível desmontar cardForm', e);
            }
            mpCardForm = null;
            mpCardFormInitialized = false;
        }
    };

    var initCardFormIfNeeded = function () {
        if (mpCardFormInitialized || !formEl || !window.MercadoPago) {
            return;
        }

        try {
            var mp = new window.MercadoPago(@json($mercadoPagoPublicKey), { locale: 'pt-BR' });
            mpCardForm = mp.cardForm({
                amount: @json($eventPriceJs),
                iframe: false,
                form: {
                    id: 'event-registration-form',
                    cardNumber: { id: 'form-checkout__cardNumber' },
                    expirationDate: { id: 'form-checkout__expirationDate' },
                    securityCode: { id: 'form-checkout__securityCode' },
                    cardholderName: { id: 'form-checkout__cardholderName' },
                    issuer: { id: 'form-checkout__issuer' },
                    installments: { id: 'form-checkout__installments' },
                    identificationType: { id: 'form-checkout__identificationType' },
                    identificationNumber: { id: 'form-checkout__identificationNumber' },
                    cardholderEmail: { id: 'form-checkout__cardholderEmail' },
                },
                callbacks: {
                    onFormMounted: function (error) {
                        if (error) {
                            console.error('Falha ao montar formulário Mercado Pago', error);
                        }
                    },
                    onSubmit: function (event) {
                        if (allowNativeSubmit) {
                            return;
                        }
                        event.preventDefault();
                        if (getSelectedPaymentMethod() !== 'card') {
                            allowNativeSubmit = true;
                            formEl.submit();
                            return;
                        }

                        syncCardPayerData();
                        var cardData = mpCardForm.getCardFormData();
                        if (!cardData || !cardData.token) {
                            setSubmitting(false);
                            alert('Não foi possível tokenizar o cartão. Verifique os dados.');
                            return;
                        }

                        if (cardBrandDisplay) {
                            cardBrandDisplay.value = cardData.paymentMethodId || '';
                        }

                        document.getElementById('card_token').value = cardData.token || '';
                        document.getElementById('card_payment_method_id').value = cardData.paymentMethodId || '';
                        document.getElementById('card_issuer_id').value =
                            cardData.issuerId || document.getElementById('form-checkout__issuer')?.value || '';
                        document.getElementById('card_installments').value =
                            cardData.installments || document.getElementById('form-checkout__installments')?.value || '1';

                        allowNativeSubmit = true;
                        formEl.submit();
                    },
                },
            });
            mpCardFormInitialized = true;
        } catch (e) {
            console.error('Erro ao iniciar Mercado Pago cardForm', e);
        }
    };

    paymentRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            toggleCardBox();
            if (getSelectedPaymentMethod() === 'card') {
                initCardFormIfNeeded();
            }
        });
    });
    toggleCardBox();
    syncCardPayerData();
    emailInput && emailInput.addEventListener('input', syncCardPayerData);
    payerDocInput && payerDocInput.addEventListener('input', syncCardPayerData);

    @if($event->is_paid && !empty($mercadoPagoPublicKey))
    if (getSelectedPaymentMethod() === 'card') {
        initCardFormIfNeeded();
    }
    @endif

    // ------- Programação: abas de dia, accordion, toggle de visualização e "acontecendo agora" -------
    var schedule = document.getElementById('evx-schedule');
    if (schedule) {
        var dayTabs = document.querySelectorAll('.evx-day-tab');
        var dayPanes = schedule.querySelectorAll('.evx-day-pane');

        var activateDay = function (paneId) {
            dayPanes.forEach(function (pane) {
                pane.classList.toggle('active', pane.id === paneId);
            });
            dayTabs.forEach(function (tab) {
                var isActive = tab.getAttribute('data-day-target') === paneId;
                tab.classList.toggle('active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        dayTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateDay(tab.getAttribute('data-day-target'));
            });
        });

        // Accordion (visão compacta)
        schedule.querySelectorAll('.evx-tl-item.has-details .evx-tl-head').forEach(function (head) {
            head.addEventListener('click', function () {
                if (schedule.classList.contains('evx-detailed')) return;
                var item = head.closest('.evx-tl-item');
                var open = item.classList.toggle('open');
                head.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        // Toggle Compacta / Detalhada
        document.querySelectorAll('.evx-view-toggle button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.evx-view-toggle button').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                schedule.classList.toggle('evx-detailed', btn.getAttribute('data-view') === 'detailed');
            });
        });

        // Destaque do item "acontecendo agora" + scroll automático
        (function () {
            var now = new Date();
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var todayStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
            var todayPane = null;
            dayPanes.forEach(function (pane) {
                if (pane.getAttribute('data-date') === todayStr) todayPane = pane;
            });
            if (!todayPane) return;

            activateDay(todayPane.id);

            var nowMinutes = now.getHours() * 60 + now.getMinutes();
            var timed = Array.prototype.filter.call(
                todayPane.querySelectorAll('.evx-tl-item[data-minutes]'),
                function () { return true; }
            );
            if (!timed.length) return;

            var current = null;
            for (var i = 0; i < timed.length; i++) {
                var start = parseInt(timed[i].getAttribute('data-minutes'), 10);
                var next = (i + 1 < timed.length) ? parseInt(timed[i + 1].getAttribute('data-minutes'), 10) : (24 * 60);
                if (nowMinutes >= start && nowMinutes < next) {
                    current = timed[i];
                    break;
                }
            }
            if (!current) return;

            current.classList.add('evx-tl-now');
            if (!window.location.hash) {
                setTimeout(function () {
                    current.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 600);
            }
        })();
    }

    var countdown = document.querySelector('.evx-countdown');
    if (countdown) {
        var eventDate = new Date(countdown.getAttribute('data-event-date')).getTime();
        var tick = function () {
            var now = Date.now();
            var diff = Math.max(0, eventDate - now);
            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            var minutes = Math.floor((diff / (1000 * 60)) % 60);
            var seconds = Math.floor((diff / 1000) % 60);
            countdown.querySelector('[data-part="days"]').textContent = days;
            countdown.querySelector('[data-part="hours"]').textContent = hours;
            countdown.querySelector('[data-part="minutes"]').textContent = minutes;
            countdown.querySelector('[data-part="seconds"]').textContent = seconds;
        };
        tick();
        setInterval(tick, 1000);
    }
});
</script>
