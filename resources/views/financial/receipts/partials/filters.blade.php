@php
    $periodStart = \Carbon\Carbon::parse($startDate);
    $periodEnd = \Carbon\Carbon::parse($endDate);
    $periodMode = 'custom';
    if ($periodStart->isSameYear($periodEnd)
        && $periodStart->month === 1 && $periodStart->day === 1
        && $periodEnd->month === 12 && $periodEnd->day === 31) {
        $periodMode = 'year';
    } elseif ($periodStart->isSameYear($periodEnd)
        && $periodStart->isSameMonth($periodEnd)
        && $periodStart->day === 1
        && $periodEnd->day === $periodStart->daysInMonth) {
        $periodMode = 'month';
    }
    $currentYear = (int) now()->year;
    $periodYears = range($currentYear - 6, $currentYear + 2);
    if (! in_array($periodStart->year, $periodYears, true)) {
        $periodYears[] = $periodStart->year;
        sort($periodYears);
    }
    $selectedTypes = is_array(request('type')) ? request('type') : (request('type') ? [request('type')] : []);
@endphp
<div class="card fr-card fr-filters-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('financial.receipts.index') }}" id="filterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="fr-filters">
                <div class="fr-filters__period" id="frPeriod">
                    <label class="form-label">Período do lançamento</label>
                    <div class="fr-period-toolbar">
                        <div class="fr-period-modes" role="group" aria-label="Modo do período">
                            <button type="button" class="btn btn-default {{ $periodMode === 'custom' ? 'is-active' : '' }}" data-period-mode="custom">Personalizado</button>
                            <button type="button" class="btn btn-default {{ $periodMode === 'month' ? 'is-active' : '' }}" data-period-mode="month">Mês</button>
                            <button type="button" class="btn btn-default {{ $periodMode === 'year' ? 'is-active' : '' }}" data-period-mode="year">Ano</button>
                        </div>
                        <div class="fr-period-shortcuts">
                            <button type="button" class="btn btn-default" data-period-shortcut="this-month">Mês atual</button>
                            <button type="button" class="btn btn-default" data-period-shortcut="last-month">Mês anterior</button>
                            <button type="button" class="btn btn-default" data-period-shortcut="this-year">Ano atual</button>
                        </div>
                    </div>
                    <div class="fr-period" data-period-panel="custom" @style(['display: none' => $periodMode !== 'custom'])>
                        <input type="date" class="form-control" id="frStartDate" name="start_date" value="{{ $periodStart->format('Y-m-d') }}">
                        <span class="fr-period__sep">até</span>
                        <input type="date" class="form-control" id="frEndDate" name="end_date" value="{{ $periodEnd->format('Y-m-d') }}">
                    </div>
                    <div data-period-panel="month" @style(['display: none' => $periodMode !== 'month'])>
                        <label class="form-label" for="frPeriodMonth">Mês</label>
                        <input type="month" class="form-control" id="frPeriodMonth" value="{{ $periodStart->format('Y-m') }}" autocomplete="off">
                    </div>
                    <div data-period-panel="year" @style(['display: none' => $periodMode !== 'year'])>
                        <select class="form-select" id="frPeriodYear">
                            @foreach($periodYears as $y)
                                <option value="{{ $y }}" @selected($periodStart->year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($tab === 'arquivos' && count($allowedTypes) > 1)
                    <div class="fr-filters__wide">
                        <span class="form-label d-block">Tipo</span>
                        <div class="fr-pills">
                            <label class="fr-pill">
                                <input type="checkbox" name="type[]" value="receita" @checked(in_array('receita', $selectedTypes, true))>
                                <span>Dízimos e ofertas</span>
                            </label>
                            <label class="fr-pill">
                                <input type="checkbox" name="type[]" value="despesa" @checked(in_array('despesa', $selectedTypes, true))>
                                <span>Despesas pagas</span>
                            </label>
                        </div>
                    </div>
                @endif
                @if($tab === 'arquivos')
                    <div>
                        <label class="form-label" for="frOrigin">Origem</label>
                        <select class="form-select" id="frOrigin" name="origin">
                            <option value="">Todas</option>
                            <option value="upload" @selected(request('origin') === 'upload')>Enviado por upload</option>
                            <option value="sistema" @selected(request('origin') === 'sistema')>Emitido pelo sistema</option>
                        </select>
                    </div>
                @endif
                <div>
                    <label class="form-label" for="frCategory">Categoria</label>
                    <select class="form-select" id="frCategory" name="category_id">
                        <option value="">Todas</option>
                        @foreach($categories->groupBy('type') as $type => $group)
                            <optgroup label="{{ $type === 'receita' ? 'Receitas' : 'Despesas' }}">
                                @foreach($group as $category)
                                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="fr-filters__wide">
                    <label class="form-label" for="frSearch">Buscar</label>
                    <input type="search" class="form-control" id="frSearch" name="search"
                           placeholder="Nº do recibo, nome, descrição, documento ou arquivo"
                           value="{{ request('search') }}">
                </div>
                <div class="fr-filters__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter me-1"></i>Aplicar filtros
                    </button>
                    @if(request()->hasAny(['type', 'origin', 'category_id', 'start_date', 'end_date', 'search']))
                        <a href="{{ route('financial.receipts.index', ['tab' => $tab]) }}" class="btn btn-default">
                            <i class="bx bx-x me-1"></i>Limpar
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@include('financial.reports.partials.filter-scripts')
