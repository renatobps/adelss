@extends('layouts.porto')

@section('title', 'Estudos')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><span>Estudos</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">Estudos</h5>
                <a href="{{ route('ensino.estudos.create') }}" class="btn btn-success btn-sm">
                    <i class="bx bx-plus me-1"></i>Adicionar estudo
                </a>
            </div>
            <div class="card-body">
                <!-- Controles superiores -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong style="color: #495057;">Resultados: {{ $studies->total() }}</strong>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Filtros -->
                        <form method="GET" action="{{ route('ensino.estudos.index') }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Buscar..." value="{{ request('search') }}" style="width: 200px;">
                        </form>
                        
                        <!-- Dropdown de resultados por página -->
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" style="width: auto;" onchange="updatePerPage(this.value)">
                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ request('per_page') == 20 || !request('per_page') ? 'selected' : '' }}>20</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <span class="small text-muted">resultados por página</span>
                        </div>

                        <!-- Botões de ação -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" title="Copiar">
                                <i class="bx bx-copy"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" title="Download">
                                    <i class="bx bx-download"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">CSV</a></li>
                                    <li><a class="dropdown-item" href="#">Excel</a></li>
                                    <li><a class="dropdown-item" href="#">PDF</a></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="window.print()" title="Imprimir">
                                <i class="bx bx-printer"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" title="Colunas">
                                    <i class="bx bx-show"></i> Colunas
                                </button>
                                <ul class="dropdown-menu">
                                    <li><label class="dropdown-item"><input type="checkbox" checked> Nome</label></li>
                                    <li><label class="dropdown-item"><input type="checkbox" checked> Criado em</label></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="cursor: pointer;" onclick="sortTable('name')">
                                    Nome
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <i class="bx bx-down-arrow-alt"></i>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('created_at')">
                                    Criado em
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <i class="bx bx-down-arrow-alt"></i>
                                </th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studies as $study)
                                <tr>
                                    <td><strong>{{ $study->name }}</strong></td>
                                    <td>{{ $study->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('ensino.estudos.edit', $study) }}" class="btn btn-primary btn-sm">
                                                <i class="bx bx-edit"></i> Editar
                                            </a>
                                            <form action="{{ route('ensino.estudos.destroy', $study) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este estudo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="bx bx-trash"></i> Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        Nenhum estudo encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if($studies->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Mostrando {{ $studies->firstItem() ?? 0 }} até {{ $studies->lastItem() ?? 0 }} de {{ $studies->total() }} resultados
                        </div>
                        <nav>
                            {{ $studies->appends(request()->query())->links() }}
                        </nav>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updatePerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        window.location.href = url.toString();
    }

    function sortTable(field) {
        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get('sort');
        const currentDirection = url.searchParams.get('direction');
        
        if (currentSort === field && currentDirection === 'asc') {
            url.searchParams.set('direction', 'desc');
        } else {
            url.searchParams.set('direction', 'asc');
        }
        url.searchParams.set('sort', field);
        
        window.location.href = url.toString();
    }
</script>
@endpush
@endsection
