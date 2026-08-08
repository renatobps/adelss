@php
    $withPerPage = $withPerPage ?? false;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="small text-muted">
        @if($sponsors->total() > 0)
            Mostrando {{ $sponsors->firstItem() }}–{{ $sponsors->lastItem() }} de {{ $sponsors->total() }} patrocinadores
        @else
            Nenhum patrocinador encontrado
        @endif
    </div>

    <div class="d-flex flex-wrap align-items-center gap-3">
        @if($withPerPage)
            <form method="GET" action="{{ route('financial.campaigns.show', $campaign) }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <input type="hidden" name="situacao" value="{{ $filters['situacao'] }}">
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <input type="hidden" name="view" value="{{ $filters['view'] }}">
                <label class="small text-muted mb-0" for="sponsorsPerPage">Mostrar</label>
                <select name="per_page" id="sponsorsPerPage" class="form-select form-select-sm" style="width:auto"
                        onchange="this.form.submit()">
                    @foreach($perPageOptions as $opt)
                        <option value="{{ $opt }}" @selected($filters['per_page'] === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                <span class="small text-muted">por página</span>
            </form>
        @endif

        @if($sponsors->hasPages())
            <div class="cs-pagination">
                {{ $sponsors->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
