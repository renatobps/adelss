@extends('layouts.porto')

@section('title', 'Detalhes do Ciclo')

@section('page-title', 'Detalhes do Ciclo')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.cycles.index') }}">Ciclos</a></li>
    <li><span>{{ $cycle->nome }}</span></li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12 text-end">
        <a href="{{ route('discipleship.cycles.edit', $cycle) }}" class="btn btn-primary">
            <i class="bx bx-edit me-1"></i>Editar
        </a>
        <a href="{{ route('discipleship.members.create', ['cycle_id' => $cycle->id]) }}" class="btn btn-success">
            <i class="bx bx-user-plus me-1"></i>Adicionar Membro
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações do Ciclo</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Nome:</dt>
                    <dd class="col-sm-9">{{ $cycle->nome }}</dd>

                    <dt class="col-sm-3">Descrição:</dt>
                    <dd class="col-sm-9">{{ $cycle->descricao ?: '-' }}</dd>

                    <dt class="col-sm-3">Data de Início:</dt>
                    <dd class="col-sm-9">{{ $cycle->data_inicio->format('d/m/Y') }}</dd>

                    <dt class="col-sm-3">Data de Fim:</dt>
                    <dd class="col-sm-9">{{ $cycle->data_fim ? $cycle->data_fim->format('d/m/Y') : '-' }}</dd>

                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9">
                        @if($cycle->status === 'ativo')
                            <span class="badge bg-success">Ativo</span>
                        @else
                            <span class="badge bg-secondary">Encerrado</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Criado por:</dt>
                    <dd class="col-sm-9">{{ $cycle->creator->name ?? '-' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">Membros do Ciclo ({{ $cycle->members->count() }})</h5>
            </div>
            <div class="card-body">
                @if($cycle->members->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Membro</th>
                                    <th>Discipulador</th>
                                    <th>Status</th>
                                    <th>Data Início</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cycle->members as $member)
                                    <tr>
                                        <td>{{ $member->member->name }}</td>
                                        <td>{{ $member->discipulador->name ?? '-' }}</td>
                                        <td>
                                            @if($member->status === 'ativo')
                                                <span class="badge bg-success">Ativo</span>
                                            @elseif($member->status === 'concluido')
                                                <span class="badge bg-info">Concluído</span>
                                            @else
                                                <span class="badge bg-warning">Pausado</span>
                                            @endif
                                        </td>
                                        <td>{{ $member->data_inicio->format('d/m/Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('discipleship.members.show', $member) }}" class="btn btn-sm btn-info" title="Visualizar">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <p>Nenhum membro vinculado a este ciclo.</p>
                        <a href="{{ route('discipleship.members.create', ['cycle_id' => $cycle->id]) }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-user-plus me-1"></i>Adicionar Membro
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
