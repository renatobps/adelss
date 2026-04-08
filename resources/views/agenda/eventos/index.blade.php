@extends('layouts.porto')

@section('title', 'Eventos')

@section('page-title', 'Eventos')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><span>Eventos</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="card-title mb-0">Resultados</h2>
                    <p class="text-muted small mb-0 mt-1">Exibe apenas eventos gerais (não cultos nem PGIs).</p>
                </div>
                <a href="{{ route('agenda.eventos.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Novo evento
                </a>
            </header>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="row g-3">
                    @forelse($events as $event)
                        <div class="col-12">
                            <div class="card border shadow-none" style="border-color: #e2ecf9 !important;">
                                <div class="card-body py-3">
                                    <div class="row align-items-center">
                                        <div class="col-auto d-none d-md-block">
                                            @if($event->banner_image)
                                                <img src="{{ \App\Models\Event::publicStorageUrl($event->banner_image) }}" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:12px;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="width:120px;height:120px;border-radius:12px;">
                                                    <i class="bx bx-calendar-event text-muted" style="font-size:2.5rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col">
                                            <h3 class="h5 mb-1" style="color:#18314d;">
                                                <a href="{{ route('agenda.eventos.registrations', $event) }}" class="text-reset text-decoration-none">{{ $event->title }}</a>
                                            </h3>
                                            <p class="text-muted small mb-0">
                                                {{ $event->start_date->format('d/m/Y') }}
                                                @if($event->end_date && !$event->start_date->isSameDay($event->end_date))
                                                    – {{ $event->end_date->format('d/m/Y') }}
                                                @endif
                                            </p>
                                            @if($event->is_paid && $event->price)
                                                <p class="mb-0 mt-1 small"><strong>Valor:</strong> R$ {{ number_format($event->price, 2, ',', '.') }}</p>
                                            @elseif(!$event->is_paid)
                                                <p class="mb-0 mt-1 small text-muted">Gratuito</p>
                                            @endif
                                        </div>
                                        <div class="col-12 col-md-auto mt-3 mt-md-0">
                                            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                                <a href="{{ route('agenda.eventos.registrations', $event) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-user-check"></i> Inscrições
                                                </a>
                                                @if($event->public_slug)
                                                    <a href="{{ route('events.public.show', $event->public_slug) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bx bx-link-external"></i> Página do evento
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="{{ url('/evento/'.$event->public_slug) }}">
                                                        <i class="bx bx-copy"></i> Copiar link
                                                    </button>
                                                @endif
                                                <form action="{{ route('agenda.eventos.duplicate', $event) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bx bx-copy"></i> Duplicar</button>
                                                </form>
                                                <a href="{{ route('agenda.eventos.edit', $event) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit"></i> Editar</a>
                                                <form action="{{ route('agenda.eventos.destroy', $event) }}" method="post" class="d-inline" onsubmit="return confirm('Remover este evento?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">Nenhum evento cadastrado. <a href="{{ route('agenda.eventos.create') }}">Criar o primeiro</a>.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-3">
                    {{ $events->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var url = btn.getAttribute('data-copy');
        navigator.clipboard.writeText(url).then(function() {
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            btn.innerHTML = '<i class="bx bx-check"></i> Copiado';
            setTimeout(function() {
                btn.classList.replace('btn-success', 'btn-outline-secondary');
                btn.innerHTML = '<i class="bx bx-copy"></i> Copiar link';
            }, 2000);
        });
    });
});
</script>
@endpush
