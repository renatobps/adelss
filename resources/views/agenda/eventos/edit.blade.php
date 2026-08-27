@extends('layouts.porto')

@section('title', 'Editar evento')

@section('page-title', 'Editar evento')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('agenda.eventos.registrations', $event) }}" class="btn btn-outline-primary btn-sm">
        <i class="bx bx-user-check"></i> Inscrições
    </a>
    <a href="{{ route('agenda.eventos.registrations', $event) }}?whatsapp=1" class="btn btn-success btn-sm">
        <i class="bx bxl-whatsapp"></i> WhatsApp aos inscritos
    </a>
</div>
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Corrija os seguintes pontos:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if($event->public_slug)
    <div class="alert alert-info">
        <strong>Link público:</strong>
        <a href="{{ route('events.public.show', $event->public_slug) }}" target="_blank">{{ url('/evento/'.$event->public_slug) }}</a>
    </div>

    <section class="card mb-3">
        <header class="card-header"><h2 class="card-title mb-0">QR Code do evento</h2></header>
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-auto text-center">
                    <div class="border rounded p-2 bg-white d-inline-block" style="line-height:0;">
                        <img src="{{ route('agenda.eventos.qrcode', $event) }}?inline=1" alt="QR Code da página do evento" style="width:150px;height:150px;">
                    </div>
                </div>
                <div class="col">
                    <p class="text-muted small mb-2">
                        Aponta para a página pública do evento. Baixe a imagem para divulgar nas redes,
                        ou gere o cartaz A4 pronto para imprimir e fixar no mural.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('agenda.eventos.qrcode', $event) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-download"></i> Baixar PNG
                        </a>
                        <a href="{{ route('agenda.eventos.qrcode', $event) }}?format=svg" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-download"></i> Baixar SVG
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="copy-event-link" data-link="{{ url('/evento/'.$event->public_slug) }}">
                            <i class="bx bx-link"></i> Copiar link do evento
                        </button>
                        <a href="{{ route('agenda.eventos.cartaz', $event) }}" class="btn btn-primary btn-sm">
                            <i class="bx bxs-file-pdf"></i> Cartaz A4 (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

<form method="post" action="{{ route('agenda.eventos.update', $event) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('agenda.eventos.partials.form', ['event' => $event, 'categories' => $categories])

    <div class="text-center mb-5">
        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="bx bx-check"></i> Salvar</button>
        <a href="{{ route('agenda.eventos.index') }}" class="btn btn-default ms-2">Voltar</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var copyBtn = document.getElementById('copy-event-link');
    if (!copyBtn) return;
    copyBtn.addEventListener('click', async function () {
        var link = copyBtn.getAttribute('data-link') || '';
        var original = copyBtn.innerHTML;
        try {
            await navigator.clipboard.writeText(link);
        } catch (e) {
            var tmp = document.createElement('textarea');
            tmp.value = link;
            document.body.appendChild(tmp);
            tmp.select();
            document.execCommand('copy');
            document.body.removeChild(tmp);
        }
        copyBtn.innerHTML = '<i class="bx bx-check"></i> Link copiado!';
        setTimeout(function () { copyBtn.innerHTML = original; }, 1500);
    });
});
</script>
@endpush
