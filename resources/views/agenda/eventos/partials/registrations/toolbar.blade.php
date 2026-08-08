<div class="er-search-sticky mb-2">
    <form method="GET" action="{{ route('agenda.eventos.registrations', $event) }}" class="row g-2 align-items-center">
        <input type="hidden" name="situacao" value="{{ $filters['situacao'] }}">

        <div class="col-12 col-md">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control"
                       placeholder="Buscar por nome, e-mail, telefone ou nº da inscrição...">
                @if($filters['q'] !== '')
                    <a href="{{ $registrationUrl(['q' => null, 'page' => null]) }}" class="btn btn-outline-secondary" title="Limpar busca">
                        <i class="bx bx-x"></i>
                    </a>
                @endif
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>

        <div class="col-7 col-md-auto">
            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Ordenar por">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-5 col-md-auto">
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Itens por página">
                @foreach([25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }} por página</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="er-chips mb-3">
    @foreach($situacoes as $key => $label)
        @php($count = $key === 'todos' ? $stats['total'] : ($stats[$key] ?? 0))
        @if($key === 'duplicadas' && $count === 0 && $filters['situacao'] !== 'duplicadas')
            @continue
        @endif
        @if($key === 'excluidas' && $count === 0 && $filters['situacao'] !== 'excluidas')
            @continue
        @endif
        <a href="{{ $registrationUrl(['situacao' => $key === 'todos' ? null : $key, 'page' => null]) }}"
           class="er-chip er-chip--{{ $key }} {{ $filters['situacao'] === $key ? 'is-active' : '' }}">
            {{ $label }} <span class="er-chip-count">({{ $count }})</span>
        </a>
    @endforeach
</div>
