@extends('layouts.porto')

@section('title', 'Check-in — '.$event->title)

@section('page-title', 'Check-in')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><a href="{{ route('agenda.eventos.registrations', $event) }}">Inscrições</a></li>
    <li><span>Check-in</span></li>
@endsection

@push('styles')
<style>
    .checkin-counter {
        font-size: 1.4rem;
        font-weight: 800;
    }
    #qr-reader {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
    }
    #qr-reader video { border-radius: 12px; }
    .checkin-result {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #fff;
        padding: 24px;
    }
    .checkin-result.show { display: flex; }
    .checkin-result.ok { background: rgba(22, 163, 74, 0.97); }
    .checkin-result.pending { background: rgba(217, 119, 6, 0.97); }
    .checkin-result.error { background: rgba(220, 38, 38, 0.97); }
    .checkin-result .icon { font-size: 4.5rem; line-height: 1; }
    .checkin-result .headline { font-size: 1.9rem; font-weight: 800; margin-top: 12px; }
    .checkin-result .who { font-size: 1.3rem; margin-top: 8px; }
    .checkin-result .num { opacity: 0.85; font-size: 1rem; margin-top: 4px; }
    .checkin-result .actions { margin-top: 26px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .checkin-result .actions .btn { min-width: 160px; min-height: 48px; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <section class="card">
            <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="card-title mb-0">Check-in</h2>
                    <p class="text-muted small mb-0 mt-1">{{ $event->title }}</p>
                </div>
                <div class="text-end">
                    <div class="checkin-counter"><span id="present-count">{{ $present }}</span> / <span id="total-count">{{ $total }}</span></div>
                    <small class="text-muted">participantes presentes</small>
                </div>
            </header>
            <div class="card-body">
                <div id="camera-error" class="alert alert-warning d-none">
                    Não foi possível acessar a câmera. Verifique as permissões do navegador
                    ou use a busca manual abaixo.
                </div>

                <div id="qr-reader"></div>

                <hr>
                <label class="form-label">Validação manual (código do QR)</label>
                <div class="d-flex gap-2">
                    <input type="text" id="manual-token" class="form-control" placeholder="Cole ou digite o código do comprovante" autocomplete="off">
                    <button type="button" class="btn btn-primary" id="manual-validate">Validar</button>
                </div>
                <small class="text-muted d-block mt-1">Use se o QR Code estiver danificado ou a câmera indisponível.</small>

                <div class="mt-3">
                    <a href="{{ route('agenda.eventos.registrations', $event) }}" class="btn btn-default btn-sm">
                        <i class="bx bx-arrow-back"></i> Voltar às inscrições
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="checkin-result" id="checkin-result">
    <div>
        <div class="icon" id="result-icon"></div>
        <div class="headline" id="result-headline"></div>
        <div class="who" id="result-name"></div>
        <div class="num" id="result-number"></div>
        <div class="actions">
            <button type="button" class="btn btn-light d-none" id="result-force">Liberar mesmo assim</button>
            <button type="button" class="btn btn-outline-light" id="result-continue">Continuar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var validateUrl = @json(route('agenda.eventos.check-in.validate', $event));
    var csrfToken = @json(csrf_token());

    var resultBox = document.getElementById('checkin-result');
    var resultIcon = document.getElementById('result-icon');
    var resultHeadline = document.getElementById('result-headline');
    var resultName = document.getElementById('result-name');
    var resultNumber = document.getElementById('result-number');
    var forceBtn = document.getElementById('result-force');
    var continueBtn = document.getElementById('result-continue');
    var presentCount = document.getElementById('present-count');
    var totalCount = document.getElementById('total-count');

    var scanner = null;
    var busy = false;
    var lastToken = null;
    var lastTokenAt = 0;

    function showResult(kind, icon, headline, data) {
        resultBox.classList.remove('ok', 'pending', 'error', 'show');
        resultBox.classList.add(kind, 'show');
        resultIcon.textContent = icon;
        resultHeadline.textContent = headline;
        resultName.textContent = data && data.name ? data.name : '';
        resultNumber.textContent = data && data.registration_number ? 'Inscrição ' + data.registration_number : '';
        forceBtn.classList.toggle('d-none', !(data && data.result === 'pending'));
    }

    function hideResult() {
        resultBox.classList.remove('show');
        busy = false;
    }

    function updateCounters(data) {
        if (typeof data.present === 'number') presentCount.textContent = data.present;
        if (typeof data.total === 'number') totalCount.textContent = data.total;
    }

    async function validate(token, force) {
        if (busy) return;
        busy = true;
        forceBtn.dataset.token = token;

        try {
            var response = await fetch(validateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ token: token, force: !!force }),
            });
            var data = await response.json();
            updateCounters(data);

            switch (data.result) {
                case 'ok':
                    showResult('ok', '\u2713', 'Entrada liberada', data);
                    break;
                case 'pending':
                    showResult('pending', '\u26A0', 'Pagamento pendente', data);
                    break;
                case 'already':
                    showResult('error', '\u2715', data.message || 'Já utilizado', data);
                    break;
                case 'cancelled':
                    showResult('error', '\u2715', 'Inscrição cancelada', data);
                    break;
                default:
                    showResult('error', '\u2715', 'Inscrição não encontrada', data);
            }
        } catch (e) {
            showResult('error', '\u2715', 'Falha de comunicação. Tente novamente.', null);
        }
    }

    forceBtn.addEventListener('click', function () {
        var token = forceBtn.dataset.token || '';
        hideResult();
        if (token) validate(token, true);
    });

    continueBtn.addEventListener('click', hideResult);

    document.getElementById('manual-validate').addEventListener('click', function () {
        var token = (document.getElementById('manual-token').value || '').trim();
        if (token) validate(token, false);
    });
    document.getElementById('manual-token').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('manual-validate').click();
        }
    });

    function onScan(decodedText) {
        var token = (decodedText || '').trim();
        if (!token || busy) return;
        // Evita revalidar o mesmo QR em sequência (câmera lê várias vezes por segundo)
        var now = Date.now();
        if (token === lastToken && (now - lastTokenAt) < 4000) return;
        lastToken = token;
        lastTokenAt = now;
        validate(token, false);
    }

    try {
        scanner = new Html5Qrcode('qr-reader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            onScan,
            function () {}
        ).catch(function () {
            document.getElementById('camera-error').classList.remove('d-none');
        });
    } catch (e) {
        document.getElementById('camera-error').classList.remove('d-none');
    }
});
</script>
@endpush
