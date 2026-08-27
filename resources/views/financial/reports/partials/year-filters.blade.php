<div class="card fr-card fr-filters-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $action }}" id="filterForm">
            <div class="fr-filters fr-filters--compact">
                <div>
                    <label class="form-label" for="frYear">Ano</label>
                    <select class="form-select" id="frYear" name="year" onchange="this.form.submit()">
                        @foreach($years as $y)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>
