@extends('layouts.porto')

@section('title', 'Detalhes do Histórico')

@section('page-title', 'Detalhes do Histórico')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.historico.index') }}">Histórico de Serviço</a></li>
    <li>Detalhes</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-info-circle me-2"></i>Detalhes do Histórico
                </h2>
            </header>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Informações do Serviço</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Data:</th>
                                <td>{{ $history->date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Voluntário:</th>
                                <td><strong>{{ $history->member->name }}</strong></td>
                            </tr>
                            <tr>
                                <th>Área:</th>
                                <td>{{ $history->serviceArea->name }}</td>
                            </tr>
                            <tr>
                                <th>Culto/Evento:</th>
                                <td>
                                    {{ $history->schedule ? $history->schedule->title : 'Escala não encontrada' }}
                                    @if($history->service_type == 'culto')
                                        <span class="badge badge-info">Culto</span>
                                    @else
                                        <span class="badge badge-primary">Evento</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($history->status == 'serviu')
                                        <span class="badge badge-success">{{ $statusLabels[$history->status] }}</span>
                                    @elseif($history->status == 'confirmado_nao_compareceu')
                                        <span class="badge badge-warning">{{ $statusLabels[$history->status] }}</span>
                                    @elseif($history->status == 'indisponivel')
                                        <span class="badge badge-info">{{ $statusLabels[$history->status] }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $statusLabels[$history->status] }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if($history->notes)
                            <tr>
                                <th>Observações:</th>
                                <td>{{ $history->notes }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('voluntarios.historico.index') }}" class="btn btn-default">
                        <i class="bx bx-arrow-back me-2"></i>Voltar
                    </a>
                    <a href="{{ route('voluntarios.historico.volunteer', $history->volunteer) }}" class="btn btn-primary">
                        <i class="bx bx-user me-2"></i>Ver Histórico do Voluntário
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
