@extends('layouts.porto')

@section('title', 'Respostas — '.$form->title)
@section('page-title', 'Respostas')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><a href="{{ route('midia.formularios.index') }}">Formulários</a></li>
    <li><span>Respostas</span></li>
@endsection

@section('content')
@include('midia.formularios.partials.alerts')

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="mb-1">{{ $form->title }}</h5>
        <p class="text-muted small mb-0">
            {{ $totalNoPeriodo }} {{ Str::plural('resposta', $totalNoPeriodo) }}
            @if($totalNoPeriodo !== $totalGeral)
                com os filtros atuais, de {{ $totalGeral }} no total
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('midia.formularios.report', array_merge([$form], request()->query())) }}" class="btn btn-sm btn-outline-primary">
            <i class="bx bx-bar-chart-alt-2"></i> Relatório
        </a>
        @can('midia.formularios.manage')
            <a href="{{ route('midia.formularios.edit', $form) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-edit"></i> Editar formulário
            </a>
        @endcan
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('midia.formularios.responses.index', $form) }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Período</label>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] }}">
                        <span class="input-group-text">–</span>
                        <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] }}">
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Buscar nas respostas</label>
                    <input type="search" class="form-control form-control-sm" name="q" value="{{ $filters['q'] }}"
                           placeholder="Nome, telefone ou conteúdo">
                </div>

                @if($filterableFields->count() > 0)
                    <div class="col-12 col-md-4 col-lg-3">
                        <label class="form-label small mb-1">Filtrar por campo</label>
                        <select class="form-select form-select-sm" name="field_id" id="filterFieldId">
                            <option value="">Qualquer campo</option>
                            @foreach($filterableFields as $campo)
                                <option value="{{ $campo->id }}" @selected((string) $filters['field_id'] === (string) $campo->id)>
                                    {{ $campo->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4 col-lg-3">
                        <label class="form-label small mb-1">Resposta do campo</label>
                        <select class="form-select form-select-sm" name="field_value" id="filterFieldValue">
                            <option value="">Qualquer resposta</option>
                            @foreach($filterableFields as $campo)
                                @foreach($campo->optionList() as $opcao)
                                    <option value="{{ $opcao }}" data-field="{{ $campo->id }}"
                                            @selected((string) $filters['field_id'] === (string) $campo->id && $filters['field_value'] === $opcao)>
                                        {{ $opcao }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bx bx-filter-alt"></i> Filtrar
                </button>
                <a href="{{ route('midia.formularios.responses.index', $form) }}" class="btn btn-sm btn-outline-secondary">
                    Limpar
                </a>
                <div class="vr d-none d-sm-block"></div>
                <a href="{{ route('midia.formularios.export.csv', array_merge([$form], request()->query())) }}"
                   class="btn btn-sm btn-success">
                    <i class="bx bx-spreadsheet"></i> Exportar Excel
                </a>
                <a href="{{ route('midia.formularios.export.pdf', array_merge([$form], request()->query())) }}"
                   class="btn btn-sm btn-danger">
                    <i class="bx bx-file"></i> Exportar PDF
                </a>
            </div>
        </form>
    </div>
</div>

@if($submissions->total() === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bx bx-message-square-x" style="font-size: 2.5rem; color: #6C757D;"></i>
            <p class="text-muted mt-3 mb-0">
                @if($totalGeral === 0)
                    Nenhuma resposta recebida ainda. Compartilhe o link público do formulário.
                @else
                    Nenhuma resposta encontrada com esses filtros.
                @endif
            </p>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 130px;">Enviada em</th>
                        @if($form->requires_identification)
                            <th style="min-width: 160px;">Nome</th>
                            <th style="min-width: 120px;">Telefone</th>
                        @endif
                        @foreach($fields as $campo)
                            <th style="min-width: 150px;">
                                {{ $campo->label }}
                                @if($campo->trashed())
                                    <i class="bx bx-info-circle text-muted" title="Campo removido do formulário"></i>
                                @endif
                            </th>
                        @endforeach
                        <th class="text-end" style="min-width: 90px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($submissions as $submission)
                        @php $respostas = $submission->answersByField(); @endphp
                        <tr>
                            <td class="text-nowrap small">
                                {{ optional($submission->submitted_at)->format('d/m/Y H:i') }}
                            </td>
                            @if($form->requires_identification)
                                <td>{{ $submission->respondent_name ?: '—' }}</td>
                                <td class="text-nowrap">{{ $submission->respondent_phone ?: '—' }}</td>
                            @endif
                            @foreach($fields as $campo)
                                @php $valor = $campo->formatValue($respostas[$campo->id] ?? null); @endphp
                                <td class="small" @if(mb_strlen($valor) > 60) title="{{ $valor }}" @endif>
                                    {{ $valor === '' ? '—' : Str::limit($valor, 60) }}
                                </td>
                            @endforeach
                            <td class="text-end text-nowrap">
                                <a href="{{ route('midia.formularios.responses.show', [$form, $submission]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Ver resposta completa">
                                    <i class="bx bx-show"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $submissions->links() }}
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var seletorCampo = document.getElementById('filterFieldId');
    var seletorValor = document.getElementById('filterFieldValue');
    if (!seletorCampo || !seletorValor) return;

    /** As opções do segundo select pertencem a campos diferentes; mostra só as do campo escolhido. */
    function filtrarOpcoes() {
        var campo = seletorCampo.value;
        var selecionadoSaiuDeCena = false;

        Array.from(seletorValor.options).forEach(function (opcao) {
            if (!opcao.dataset.field) return;

            var visivel = campo !== '' && opcao.dataset.field === campo;
            opcao.hidden = !visivel;
            opcao.disabled = !visivel;

            if (!visivel && opcao.selected) {
                selecionadoSaiuDeCena = true;
            }
        });

        if (selecionadoSaiuDeCena) {
            seletorValor.value = '';
        }

        seletorValor.disabled = campo === '';
    }

    seletorCampo.addEventListener('change', filtrarOpcoes);
    filtrarOpcoes();
})();
</script>
@endpush
