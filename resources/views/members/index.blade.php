@extends('layouts.porto')

@section('title', 'Membros')

@section('page-title', 'Membros')

@section('breadcrumbs')
    <li><span>Membros</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-user me-2"></i>Membros
                </h2>
                <p class="card-subtitle">Gerencie os membros da organização</p>
            </header>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('import_errors') && count(session('import_errors')) > 0)
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">
                            <i class="bx bx-error-circle me-2"></i>Erros de Importação Detalhados
                        </h5>
                        <p class="mb-2">Foram encontrados <strong>{{ count(session('import_errors')) }} erro(s)</strong> durante a importação:</p>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @php
                    $user = Auth::user();
                    $isAdmin = $user?->is_admin ?? false;
                    $canCreateMembers = $isAdmin || 
                                       ($user && ($user->hasPermission('members.index.create') || 
                                                  $user->hasPermission('members.create') ||
                                                  $user->hasPermission('members.index.manage')));
                    $canEditMembers = $isAdmin || 
                                     ($user && ($user->hasPermission('members.index.edit') || 
                                                $user->hasPermission('members.edit') ||
                                                $user->hasPermission('members.index.manage')));
                    $canDeleteMembers = $isAdmin || 
                                       ($user && ($user->hasPermission('members.index.delete') || 
                                                  $user->hasPermission('members.delete') ||
                                                  $user->hasPermission('members.index.manage')));
                @endphp
                
                <!-- Card Tutorial de Importação -->
                @if($canCreateMembers)
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-bulb me-2"></i> Como Cadastrar Vários Membros de Uma Vez?
                            <button class="btn btn-sm btn-light float-end" type="button" data-bs-toggle="collapse" data-bs-target="#tutorialImportacao" aria-expanded="false">
                                <i class="bx bx-help-circle me-1"></i> Ver Tutorial
                            </button>
                        </h5>
                    </div>
                    <div class="collapse" id="tutorialImportacao">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="text-success"><i class="bx bx-1 me-1"></i> Baixe o Template CSV</h6>
                                    <p>Primeiro, baixe nosso arquivo de exemplo com o formato correto:</p>
                                    <div class="mb-3">
                                        <a href="{{ route('members.import.template') }}" class="btn btn-success btn-lg">
                                            <i class="bx bx-download me-2"></i> Baixar Template (Arquivo de Exemplo)
                                        </a>
                                    </div>

                                    <hr>

                                    <h6 class="text-success"><i class="bx bx-2 me-1"></i> Preencha os Dados</h6>
                                    <p>Abra o arquivo baixado no <strong>Excel</strong>, <strong>Google Sheets</strong> ou <strong>LibreOffice</strong>:</p>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>nome</th>
                                                    <th>email</th>
                                                    <th>telefone</th>
                                                    <th>status</th>
                                                    <th>genero</th>
                                                    <th>estado_civil</th>
                                                    <th>data_nascimento</th>
                                                    <th>data_membresia</th>
                                                    <th>cargo_id</th>
                                                    <th>departamento_id</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>João Silva</td>
                                                    <td>joao@email.com</td>
                                                    <td>11999999999</td>
                                                    <td>ativo</td>
                                                    <td>M</td>
                                                    <td>casado</td>
                                                    <td>1990-05-15</td>
                                                    <td>2020-01-10</td>
                                                    <td>1</td>
                                                    <td>1</td>
                                                </tr>
                                                <tr>
                                                    <td>Maria Santos</td>
                                                    <td>maria@email.com</td>
                                                    <td>11988888888</td>
                                                    <td>ativo</td>
                                                    <td>F</td>
                                                    <td>solteiro</td>
                                                    <td>1995-08-20</td>
                                                    <td>2021-03-05</td>
                                                    <td>1</td>
                                                    <td>1,2</td>
                                                </tr>
                                                <tr>
                                                    <td>Pedro Oliveira</td>
                                                    <td></td>
                                                    <td>11977777777</td>
                                                    <td>visitante</td>
                                                    <td>M</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>2</td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="alert alert-info">
                                        <strong><i class="bx bx-info-circle me-2"></i> Regras importantes:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li><strong>nome:</strong> Obrigatório - Nome completo (ex: João Silva)</li>
                                            <li><strong>email:</strong> Opcional, mas se preencher deve ser válido</li>
                                            <li><strong>telefone:</strong> Opcional, com DDD, apenas números (ex: 11999999999)</li>
                                            <li><strong>status:</strong> Opcional - Use: ativo, inativo, visitante ou membro_transferido (padrão: ativo)</li>
                                            <li><strong>genero:</strong> Opcional - Use: M (Masculino) ou F (Feminino)</li>
                                            <li><strong>estado_civil:</strong> Opcional - Use: solteiro, casado, divorciado, viuvo ou uniao_estavel</li>
                                            <li><strong>data_nascimento:</strong> Opcional - Formato: YYYY-MM-DD ou DD/MM/YYYY (ex: 1990-05-15)</li>
                                            <li><strong>data_membresia:</strong> Opcional - Formato: YYYY-MM-DD ou DD/MM/YYYY (ex: 2020-01-10)</li>
                                            <li><strong>cargo_id:</strong> Opcional - ID do cargo (verifique na página de Cargos)</li>
                                            <li><strong>departamento_id:</strong> Opcional - ID(s) do(s) departamento(s). Para múltiplos, separe por vírgula (ex: 1,2,3)</li>
                                        </ul>
                                    </div>

                                    <hr>

                                    <h6 class="text-success"><i class="bx bx-3 me-1"></i> Salve como CSV</h6>
                                    <ul>
                                        <li><strong>Excel:</strong> Arquivo → Salvar Como → CSV (separado por vírgulas)</li>
                                        <li><strong>Google Sheets:</strong> Arquivo → Fazer download → CSV</li>
                                    </ul>

                                    <hr>

                                    <h6 class="text-success"><i class="bx bx-4 me-1"></i> Importe o Arquivo</h6>
                                    <p>Clique no botão verde abaixo:</p>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#importModal">
                                            <i class="bx bx-upload me-2"></i> Ir para Importação
                                        </button>
                                    </div>

                                    <p>Depois:</p>
                                    <ol>
                                        <li>Escolha o arquivo CSV que você salvou</li>
                                        <li>Clique em "Importar"</li>
                                        <li>Aguarde o resultado: "Sucessos: X, Erros: Y"</li>
                                    </ol>

                                    <div class="alert alert-success">
                                        <strong><i class="bx bx-check-circle me-2"></i> Pronto!</strong> Todos os membros válidos foram cadastrados no sistema.
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0"><i class="bx bx-star me-2"></i> Dicas Rápidas</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="small mb-0">
                                                <li class="mb-2">✅ Cadastre centenas de membros em segundos</li>
                                                <li class="mb-2">✅ O sistema valida tudo automaticamente</li>
                                                <li class="mb-2">✅ Telefones duplicados são ignorados</li>
                                                <li class="mb-2">✅ Você recebe relatório de erros detalhado</li>
                                                <li class="mb-2">⚠️ Não precisa formatar o telefone (pode ter parênteses, traços, etc)</li>
                                                <li class="mb-2">⚠️ Email pode ficar vazio</li>
                                                <li class="mb-2">⚠️ Verifique o ID da categoria antes de importar</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="card mt-3">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="bx bx-play-circle me-2"></i> Vídeo Tutorial</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <i class="bx bxl-youtube" style="font-size: 4rem; color: #ff0000;"></i>
                                            <p class="small text-muted mt-2">Em breve: vídeo passo a passo</p>
                                        </div>
                                    </div>

                                    <div class="card mt-3 border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0"><i class="bx bx-error me-2"></i> Erros Comuns</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="small mb-0">
                                                <li class="mb-2"><strong>Telefone inválido:</strong> Use 10+ dígitos</li>
                                                <li class="mb-2"><strong>Email inválido:</strong> Formato email@dominio.com</li>
                                                <li class="mb-2"><strong>Telefone duplicado:</strong> Já existe no sistema</li>
                                                <li class="mb-2"><strong>Nome vazio:</strong> Campo obrigatório</li>
                                                <li class="mb-2"><strong>Categoria inválida:</strong> Verifique o ID na página de Categorias</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($canCreateMembers)
                <div class="d-flex justify-content-end align-items-center mb-4">
                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bx bx-upload me-2"></i>Importar CSV
                    </button>
                    <a href="{{ route('members.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-2"></i>Novo Membro
                    </a>
                </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Filtros -->
                <div class="card mb-4">
                    <header class="card-header">
                        <h2 class="card-title">Filtros</h2>
                    </header>
                    <div class="card-body">
                        <form method="GET" action="{{ route('members.index') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="{{ request('search') }}" placeholder="Nome, email, telefone ou CPF">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="ativo" {{ request('status') == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                    <option value="inativo" {{ request('status') == 'inativo' ? 'selected' : '' }}>Inativo</option>
                                    <option value="visitante" {{ request('status') == 'visitante' ? 'selected' : '' }}>Visitante</option>
                                    <option value="membro_transferido" {{ request('status') == 'membro_transferido' ? 'selected' : '' }}>Transferido</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="gender" class="form-label">Gênero</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Todos</option>
                                    <option value="M" {{ request('gender') == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ request('gender') == 'F' ? 'selected' : '' }}>Feminino</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="sort_by" class="form-label">Ordenar por</label>
                                <select class="form-select" id="sort_by" name="sort_by">
                                    <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Nome</option>
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Data de Cadastro</option>
                                    <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Status</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="per_page" class="form-label">Itens por página</label>
                                <select class="form-select" id="per_page" name="per_page" onchange="this.form.submit()">
                                    <option value="10" {{ (request('per_page', 10)) == '10' ? 'selected' : '' }}>10</option>
                                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-filter me-2"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if($members->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>Gênero</th>
                                    <th>Status</th>
                                    <th>Departamento</th>
                                    <th>PGI</th>
                                    <th>Cargo</th>
                                    <th width="150">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    <tr>
                                        <td>
                                            @if($member->photo_url)
                                                <img src="{{ $member->photo_url }}" 
                                                     alt="{{ $member->name }}" 
                                                     class="rounded-circle" 
                                                     width="40" 
                                                     height="40"
                                                     style="object-fit: cover;">
                                            @else
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('members.show', $member) }}" class="text-primary">
                                                {{ $member->name }}
                                            </a>
                                        </td>
                                        <td>{{ $member->email ?? '-' }}</td>
                                        <td>{{ $member->phone ?? '-' }}</td>
                                        <td>
                                            @if($member->gender == 'M')
                                                <span class="badge badge-info">Masculino</span>
                                            @elseif($member->gender == 'F')
                                                <span class="badge badge-danger">Feminino</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($member->status == 'ativo')
                                                <span class="badge badge-success">Ativo</span>
                                            @elseif($member->status == 'inativo')
                                                <span class="badge badge-secondary">Inativo</span>
                                            @elseif($member->status == 'visitante')
                                                <span class="badge badge-warning">Visitante</span>
                                            @else
                                                <span class="badge badge-danger">Transferido</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($member->departments->count() > 0)
                                                @foreach($member->departments as $dept)
                                                    <span class="badge badge-info">{{ $dept->name }}</span>
                                                @endforeach
                                            @elseif($member->department)
                                                <span class="badge badge-info">{{ $member->department->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $member->pgi->name ?? '-' }}</td>
                                        <td>
                                            @if($member->role)
                                                <span class="badge badge-success">{{ $member->role->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('members.show', $member) }}" 
                                                   class="btn btn-default" 
                                                   title="Visualizar">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if($canEditMembers)
                                                <a href="{{ route('members.edit', $member) }}" 
                                                   class="btn btn-default" 
                                                   title="Editar">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @endif
                                                @if($canDeleteMembers)
                                                <form action="{{ route('members.destroy', $member) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este membro?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-default" 
                                                            title="Excluir">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $members->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum membro encontrado.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<!-- Modal: Importar Membros -->
@if($canCreateMembers ?? false)
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bx bx-upload me-2"></i>Importar Membros
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('members.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Formato do arquivo CSV:</strong><br>
                        O arquivo deve conter as colunas: nome, email, telefone, status, genero, estado_civil, data_nascimento, data_membresia, cargo_id, departamento_id, separadas por vírgula (,).<br>
                        <small>Baixe o template para ver o formato correto. Campos opcionais podem ficar vazios.</small>
                    </div>
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Selecionar arquivo CSV <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('import_file') is-invalid @enderror" 
                               id="import_file" name="import_file" accept=".csv,.txt" required>
                        @error('import_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Tamanho máximo: 10MB. Formato: CSV</small>
                    </div>
                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-danger">
                            <strong>Erros encontrados:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="alert alert-warning">
                        <i class="bx bx-error me-2"></i>
                        <strong>Atenção:</strong> Telefones duplicados serão ignorados. Verifique o template antes de importar.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-upload me-2"></i>Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('members.index') }}"]');
    const searchInput = document.getElementById('search');
    const statusSelect = document.getElementById('status');
    const genderSelect = document.getElementById('gender');
    const sortSelect = document.getElementById('sort_by');
    
    let searchTimeout;
    
    // Função para submeter o formulário com debounce
    function submitForm() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            form.submit();
        }, 500); // Aguarda 500ms após parar de digitar
    }
    
    // Busca em tempo real no campo de busca
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            submitForm();
        });
        
        // Permite Enter para buscar imediatamente
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                e.preventDefault();
                form.submit();
            }
        });
    }
    
    // Filtros de select também atualizam automaticamente
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            form.submit();
        });
    }
    
    if (genderSelect) {
        genderSelect.addEventListener('change', function() {
            form.submit();
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            form.submit();
        });
    }
});
</script>
@endpush
@endsection
