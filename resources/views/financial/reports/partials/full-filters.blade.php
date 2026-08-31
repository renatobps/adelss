@php
    $periodStart = \Carbon\Carbon::parse($startDate ?? now()->startOfMonth());
    $periodEnd = \Carbon\Carbon::parse($endDate ?? now()->endOfMonth());
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
@endphp
<div class="card fr-card fr-filters-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $action }}" id="filterForm">
            <div class="fr-filters">
                <div class="fr-filters__period" id="frPeriod">
                    <label class="form-label">Período</label>
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
                @include('financial.reports.partials.type-status', [
                    'defaultTypes' => $defaultTypes ?? [],
                    'defaultStatus' => $defaultStatus ?? [],
                ])
                <div>
                    <label class="form-label">Conta</label>
                    <select class="form-select" name="account_id">
                        <option value="">Todas</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Centro de custo</label>
                    <select class="form-select" name="cost_center_id">
                        <option value="">Todos</option>
                        @foreach($costCenters as $costCenter)
                            <option value="{{ $costCenter->id }}" @selected(request('cost_center_id') == $costCenter->id)>{{ $costCenter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Categoria receitas</label>
                    <select class="form-select" name="category_receitas_id">
                        <option value="">Todas</option>
                        @foreach($categoriesReceitas as $category)
                            <option value="{{ $category->id }}" @selected(request('category_receitas_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Categoria despesas</label>
                    <select class="form-select" name="category_despesas_id">
                        <option value="">Todas</option>
                        @foreach($categoriesDespesas as $category)
                            <option value="{{ $category->id }}" @selected(request('category_despesas_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!empty($showSearch))
                    <div class="fr-filters__wide">
                        <label class="form-label" for="frSearch">Buscar</label>
                        <input type="search" class="form-control" id="frSearch" name="search" placeholder="Descrição" value="{{ request('search') }}">
                    </div>
                @endif
                <div class="fr-filters__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter me-1"></i>Aplicar filtros
                    </button>
                    @if(request()->hasAny(['type', 'status', 'category_receitas_id', 'category_despesas_id', 'account_id', 'cost_center_id', 'start_date', 'end_date', 'search']))
                        <a href="{{ $action }}" class="btn btn-default">
                            <i class="bx bx-x me-1"></i>Limpar
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@include('financial.reports.partials.filter-scripts')
