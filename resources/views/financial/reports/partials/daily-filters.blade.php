<div class="card fr-card fr-filters-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $action }}" id="filterForm">
            <div class="fr-filters">
                <div class="fr-filters__period">
                    <label class="form-label">Período</label>
                    <div class="fr-period">
                        <input type="date" class="form-control" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                        <span class="fr-period__sep">até</span>
                        <input type="date" class="form-control" name="end_date" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
                    </div>
                </div>
                <div>
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="category_id">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                <div class="fr-filters__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter me-1"></i>Aplicar filtros
                    </button>
                    @if(request()->hasAny(['category_id', 'account_id', 'cost_center_id', 'start_date', 'end_date', 'search']))
                        <a href="{{ $action }}" class="btn btn-default">
                            <i class="bx bx-x me-1"></i>Limpar
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
