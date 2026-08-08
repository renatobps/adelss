<div class="cs-search-sticky mb-2">
    <form method="GET" action="{{ route('financial.campaigns.show', $campaign) }}" class="row g-2 align-items-center">
        <input type="hidden" name="situacao" value="{{ $filters['situacao'] }}">
        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
        <input type="hidden" name="view" value="{{ $filters['view'] }}">

        <div class="col-12 col-md">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control"
                       placeholder="Buscar por nome ou telefone...">
                @if($filters['q'] !== '')
                    <a href="{{ $sponsorUrl(['q' => null, 'page' => null]) }}" class="btn btn-outline-secondary" title="Limpar busca">
                        <i class="bx bx-x"></i>
                    </a>
                @endif
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>

        <div class="col-8 col-md-auto">
            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Ordenar por">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-4 col-md-auto">
            <div class="btn-group btn-group-sm w-100" role="group" aria-label="Modo de visualização">
                <a href="{{ $sponsorUrl(['view' => 'lista', 'page' => null]) }}"
                   class="btn {{ $filters['view'] === 'lista' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Lista">
                    <i class="bx bx-list-ul"></i>
                </a>
                <a href="{{ $sponsorUrl(['view' => 'tabela', 'page' => null]) }}"
                   class="btn {{ $filters['view'] === 'tabela' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Tabela">
                    <i class="bx bx-table"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<div class="cs-chips mb-2">
    <a href="{{ $sponsorUrl(['situacao' => null, 'page' => null]) }}"
       class="cs-chip {{ $filters['situacao'] === 'todos' ? 'is-active' : '' }}">
        Todos <span class="cs-chip-count">({{ $counts['todos'] }})</span>
    </a>
    @foreach(\App\Models\CampaignSponsor::SITUACOES as $key => $label)
        <a href="{{ $sponsorUrl(['situacao' => $key, 'page' => null]) }}"
           class="cs-chip cs-chip--{{ $key }} {{ $filters['situacao'] === $key ? 'is-active' : '' }}">
            {{ $label }} <span class="cs-chip-count">({{ $counts[$key] }})</span>
        </a>
    @endforeach
</div>

@if($filters['situacao'] === 'em_atraso' && $canPay && $counts['em_atraso'] > 0)
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#chargeOverdueModal">
            <i class="bx bxl-whatsapp me-1"></i>Cobrar todos em atraso ({{ $counts['em_atraso'] }})
        </button>
    </div>
@endif
