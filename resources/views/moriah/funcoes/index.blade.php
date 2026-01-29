@extends('layouts.porto')

@section('title', 'Funções - Moriah')

@section('page-title', 'Funções')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><span>Funções</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="mb-1" style="color: #333; font-weight: 600;">Funções</h2>
            <h3 class="mb-4" style="color: #666; font-size: 1.1rem; font-weight: 500;">MORIAH MUSIC</h3>
        </div>

        <!-- Botão Adicionar -->
        <div class="mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#funcaoModal" onclick="resetForm()">
                <i class="bx bx-plus me-1"></i>Nova Função
            </button>
        </div>

        <!-- Lista de Funções -->
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body p-0">
                @if($functions->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($functions as $funcao)
                            <div class="list-group-item d-flex justify-content-between align-items-center" style="border: none; border-bottom: 1px solid #eee; padding: 16px 20px;">
                                <div class="d-flex align-items-center">
                                    @if($funcao->icon)
                                        <img src="{{ asset('img/img/icon8/' . $funcao->icon) }}" 
                                             alt="{{ $funcao->name }}" 
                                             style="width: 40px; height: 40px; object-fit: contain; margin-right: 12px;">
                                    @else
                                        <i class="bx bx-music me-3" style="font-size: 2rem; color: #666;"></i>
                                    @endif
                                    <span style="font-size: 1rem; color: #333;">{{ $funcao->name }}</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editFuncao({{ $funcao->id }}, '{{ $funcao->name }}', '{{ $funcao->icon ?? '' }}', {{ $funcao->order }})">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <form action="{{ route('moriah.funcoes.destroy', $funcao) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta função?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-music" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma função cadastrada.</p>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#funcaoModal" onclick="resetForm()">
                            <i class="bx bx-plus me-1"></i>Cadastrar primeira função
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar/Editar Função -->
<div class="modal fade" id="funcaoModal" tabindex="-1" aria-labelledby="funcaoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="funcaoModalLabel">Nova Função</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="funcaoForm" method="POST">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body">
                    <!-- Campo Nome -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">
                            <i class="bx bx-abc me-2"></i>Nome *
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" required 
                               value="{{ old('name') }}" 
                               placeholder="Digite o nome da função">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Ícone -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Ícone</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="iconInput" name="icon" 
                                   placeholder="Digite a classe do ícone (ex: bx-music)" 
                                   value="{{ old('icon') }}">
                            <small class="text-muted">Ou selecione um ícone abaixo</small>
                        </div>
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                            <div class="row g-2" id="iconGrid">
                                <!-- Ícones serão inseridos aqui via JavaScript -->
                            </div>
                            <div id="iconGridLoading" class="text-center py-3" style="display: none;">
                                <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem; color: #666;"></i>
                                <p class="text-muted mt-2">Carregando ícones...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Campo Ordem -->
                    <div class="mb-3">
                        <label for="order" class="form-label fw-bold">Ordem</label>
                        <input type="number" class="form-control" id="order" name="order" 
                               value="{{ old('order', 0) }}" min="0">
                        <small class="text-muted">Define a ordem de exibição (menor número aparece primeiro)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('{{ session('success') }}');
        });
    </script>
@endif

<script>
    // Lista de ícones disponíveis (da pasta icon8)
    const musicIcons = [
        {
            name: 'Audition',
            file: 'icons8-audition-100.png',
            label: 'Audition'
        },
        {
            name: 'Baixo',
            file: 'icons8-bass-guitar-100.png',
            label: 'Baixo'
        },
        {
            name: 'Coral',
            file: 'icons8-choir-100.png',
            label: 'Coral'
        },
        {
            name: 'Bateria',
            file: 'icons8-drum-set-100.png',
            label: 'Bateria'
        },
        {
            name: 'Violão',
            file: 'icons8-guitar-100.png',
            label: 'Violão'
        },
        {
            name: 'Regente',
            file: 'icons8-music-conductor-100.png',
            label: 'Regente'
        },
        {
            name: 'Piano',
            file: 'icons8-piano-100.png',
            label: 'Piano'
        },
        {
            name: 'Estúdio',
            file: 'icons8-radio-studio-100.png',
            label: 'Estúdio'
        },
        {
            name: 'Rock',
            file: 'icons8-rock-music-100.png',
            label: 'Rock'
        }
    ];

    // Renderizar grid de ícones
    function renderIconGrid() {
        const grid = document.getElementById('iconGrid');
        const loading = document.getElementById('iconGridLoading');
        
        if (!grid) return;
        
        // Mostrar loading
        if (loading) loading.style.display = 'block';
        grid.innerHTML = '';
        
        // Pequeno delay para garantir que o DOM está pronto
        setTimeout(() => {
            musicIcons.forEach(icon => {
                const col = document.createElement('div');
                col.className = 'col-3 col-md-2 col-lg-1 text-center';
                col.style.cursor = 'pointer';
                col.style.padding = '12px';
                col.style.borderRadius = '8px';
                col.style.transition = 'all 0.2s';
                col.style.minHeight = '80px';
                col.style.display = 'flex';
                col.style.flexDirection = 'column';
                col.style.alignItems = 'center';
                col.style.justifyContent = 'center';
                col.style.border = '2px solid transparent';
                col.title = icon.label;
                
                const iconImg = document.createElement('img');
                iconImg.src = '{{ asset("img/img/icon8") }}/' + icon.file;
                iconImg.alt = icon.label;
                iconImg.style.width = '48px';
                iconImg.style.height = '48px';
                iconImg.style.objectFit = 'contain';
                iconImg.style.marginBottom = '4px';
                col.appendChild(iconImg);
                
                const labelSpan = document.createElement('span');
                labelSpan.textContent = icon.label;
                labelSpan.style.fontSize = '0.75rem';
                labelSpan.style.color = '#666';
                labelSpan.style.textAlign = 'center';
                col.appendChild(labelSpan);
                
                col.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.backgroundColor = '#e9ecef';
                        this.style.borderColor = '#dee2e6';
                    }
                });
                
                col.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.backgroundColor = 'transparent';
                        this.style.borderColor = 'transparent';
                    }
                });
                
                col.addEventListener('click', function() {
                    document.getElementById('iconInput').value = icon.file;
                    // Destacar ícone selecionado
                    document.querySelectorAll('#iconGrid .col').forEach(c => {
                        c.style.backgroundColor = 'transparent';
                        c.style.borderColor = 'transparent';
                        c.classList.remove('selected');
                        const span = c.querySelector('span');
                        if (span) span.style.color = '#666';
                    });
                    this.style.backgroundColor = '#007bff';
                    this.style.borderColor = '#007bff';
                    this.style.borderRadius = '8px';
                    this.classList.add('selected');
                    labelSpan.style.color = 'white';
                });
                
                grid.appendChild(col);
            });
            
            // Esconder loading
            if (loading) loading.style.display = 'none';
        }, 50);
    }

    // Resetar formulário
    function resetForm() {
        document.getElementById('funcaoForm').action = '{{ route('moriah.funcoes.store') }}';
        document.getElementById('funcaoForm').method = 'POST';
        document.getElementById('methodField').innerHTML = '@csrf';
        document.getElementById('funcaoModalLabel').textContent = 'Nova Função';
        document.getElementById('name').value = '';
        document.getElementById('iconInput').value = '';
        document.getElementById('order').value = '0';
        
        // Limpar seleção de ícones
        document.querySelectorAll('#iconGrid .col').forEach(c => {
            c.style.backgroundColor = 'transparent';
            c.style.borderColor = 'transparent';
            c.classList.remove('selected');
            const span = c.querySelector('span');
            if (span) span.style.color = '#666';
        });
    }

    // Editar função
    function editFuncao(id, name, icon, order) {
        document.getElementById('funcaoForm').action = '{{ route('moriah.funcoes.update', ':id') }}'.replace(':id', id);
        document.getElementById('funcaoForm').method = 'POST';
        document.getElementById('methodField').innerHTML = '@csrf @method('PUT')';
        document.getElementById('funcaoModalLabel').textContent = 'Editar Função';
        document.getElementById('name').value = name;
        document.getElementById('iconInput').value = icon;
        document.getElementById('order').value = order;
        
        const modal = new bootstrap.Modal(document.getElementById('funcaoModal'));
        
        // Renderizar grid quando modal for aberto
        const modalElement = document.getElementById('funcaoModal');
        modalElement.addEventListener('shown.bs.modal', function() {
            renderIconGrid();
            
            // Destacar ícone selecionado após renderizar
            setTimeout(() => {
                if (icon) {
                    const iconCols = document.querySelectorAll('#iconGrid .col');
                    iconCols.forEach(col => {
                        const iconImg = col.querySelector('img');
                        if (iconImg && iconImg.src.includes(icon)) {
                            col.style.backgroundColor = '#007bff';
                            col.style.borderColor = '#007bff';
                            col.style.borderRadius = '8px';
                            col.classList.add('selected');
                            const span = col.querySelector('span');
                            if (span) span.style.color = 'white';
                        }
                    });
                }
            }, 150);
        }, { once: true });
        
        modal.show();
    }

    // Renderizar grid quando modal for aberto
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('funcaoModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', function() {
                renderIconGrid();
            });
        }
    });
</script>
@endsection
