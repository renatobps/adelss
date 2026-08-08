@extends('layouts.porto')

@php
    $total = $candidates->sum(fn ($s) => (float) ($reminders->overdueContext($s)['amount'] ?? 0));
@endphp

@section('title', 'Próximo lote de lembretes — ' . $campaign->name)
@section('page-title', 'Próximo lote de lembretes')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    <li><a href="{{ route('financial.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
    <li><a href="{{ route('financial.campaigns.reminders.edit', $campaign) }}">Lembretes</a></li>
    <li><span>Próximo lote</span></li>
@endsection

@section('content')
@include('financial.campaigns.partials.alerts')

<section class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1">
                    {{ $candidates->count() }} {{ Str::plural('patrocinador', $candidates->count()) }},
                    R$ {{ number_format($total, 2, ',', '.') }} em atraso
                </h5>
                <p class="text-muted small mb-0">
                    @if($nextRun)
                        Envio previsto para <strong>{{ $nextRun->format('d/m/Y \à\s H:i') }}</strong>.
                    @elseif($settings->paused)
                        Lembretes pausados nesta campanha — nada será enviado até você reativar.
                    @else
                        Lembretes desativados nesta campanha — esta lista é apenas uma simulação.
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('financial.campaigns.reminders.edit', $campaign) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bx bx-cog me-1"></i>Configurar
                </a>
                <a href="{{ route('financial.campaigns.show', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</section>

<section class="card">
    <header class="card-header py-2">
        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Destinatários</h6>
    </header>
    <div class="card-body p-0">
        @if($candidates->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bx bx-check-circle fs-1 d-block mb-2"></i>
                Ninguém está elegível para lembrete neste momento.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Patrocinador</th>
                            <th>Telefone</th>
                            <th class="text-center">Parcelas em atraso</th>
                            <th class="text-end">Valor</th>
                            <th class="text-center">Dias</th>
                            <th class="text-center">Lembretes enviados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidates as $sponsor)
                            @php $context = $reminders->overdueContext($sponsor); @endphp
                            <tr>
                                <td>{{ $sponsor->name }}</td>
                                <td class="text-muted small">{{ $sponsor->phone }}</td>
                                <td class="text-center">{{ $context['count'] }}</td>
                                <td class="text-end">R$ {{ number_format($context['amount'], 2, ',', '.') }}</td>
                                <td class="text-center">{{ $context['days'] }}</td>
                                <td class="text-center">
                                    {{ $reminders->remindersSentFor($sponsor, $context['oldest']->id) }}/{{ $settings->max_reminders }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($candidates->isNotEmpty())
        <div class="card-footer text-muted small">
            Para tirar alguém deste lote, desative os lembretes do patrocinador na tela da campanha,
            ou pause os lembretes da campanha inteira na configuração.
        </div>
    @endif
</section>
@endsection
