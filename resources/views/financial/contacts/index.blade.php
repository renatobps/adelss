@extends('layouts.porto')

@section('title', 'Contatos')

@section('page-title', 'Contatos')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Contatos</span></li>
@endsection

@section('content')
<!-- Header -->
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Organize seus contatos por categorias.
</div>

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

<div class="row">
    <!-- Painel Esquerdo: Lista de Contatos -->
    <div class="col-lg-8 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <!-- Tabs -->
            <div class="card-header p-0 border-bottom">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="todos-tab" data-bs-toggle="tab" data-bs-target="#todos" type="button" role="tab">
                            Todos ({{ $total }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="categoria-tab" data-bs-toggle="tab" data-bs-target="#criar-categoria" type="button" role="tab">
                            + Criar categoria
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    <!-- Tab: Todos -->
                    <div class="tab-pane fade show active" id="todos" role="tabpanel">
                        @if($contacts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nome</th>
                                            <th>E-mail</th>
                                            <th>Telefone 1</th>
                                            <th>Telefone 2</th>
                                            <th class="text-end" style="width: 100px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contacts as $contact)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $contact->name }}</strong>
                                                        @if($contact->category)
                                                            <br><small class="text-muted">{{ $contact->category->name }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $contact->email ?? '-' }}</td>
                                                <td>{{ $contact->phone_1 ?? '-' }}</td>
                                                <td>{{ $contact->phone_2 ?? '-' }}</td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $contact->id }}" title="Editar">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <form action="{{ route('financial.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este contato?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Remover">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- Modal de Edição -->
                                            <div class="modal fade" id="editModal{{ $contact->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $contact->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel{{ $contact->id }}">
                                                                <i class="bx bx-edit me-2"></i>Editar Contato
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                        </div>
                                                        <form action="{{ route('financial.contacts.update', $contact) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-3">
                                                                        <label for="edit_name{{ $contact->id }}" class="form-label">Nome <span class="text-danger">*</span></label>
                                                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                                               id="edit_name{{ $contact->id }}" name="name" 
                                                                               value="{{ old('name', $contact->name) }}" required>
                                                                        @error('name')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label for="edit_email{{ $contact->id }}" class="form-label">E-mail</label>
                                                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                                                               id="edit_email{{ $contact->id }}" name="email" 
                                                                               value="{{ old('email', $contact->email) }}">
                                                                        @error('email')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-3">
                                                                        <label for="edit_category{{ $contact->id }}" class="form-label">Categoria</label>
                                                                        <select class="form-select" id="edit_category{{ $contact->id }}" name="category_id">
                                                                            <option value="">Nenhum</option>
                                                                            @foreach($categories as $category)
                                                                                <option value="{{ $category->id }}" {{ old('category_id', $contact->category_id) == $category->id ? 'selected' : '' }}>
                                                                                    {{ $category->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                                                        <select class="form-select" id="edit_type{{ $contact->id }}" name="type" required>
                                                                            <option value="pessoa_fisica" {{ old('type', $contact->type) == 'pessoa_fisica' ? 'selected' : '' }}>Pessoa física</option>
                                                                            <option value="pessoa_juridica" {{ old('type', $contact->type) == 'pessoa_juridica' ? 'selected' : '' }}>Pessoa jurídica</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-3">
                                                                        <label for="edit_phone_1{{ $contact->id }}" class="form-label">Telefone 1</label>
                                                                        <input type="text" class="form-control" id="edit_phone_1{{ $contact->id }}" name="phone_1" 
                                                                               value="{{ old('phone_1', $contact->phone_1) }}">
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label for="edit_phone_2{{ $contact->id }}" class="form-label">Telefone 2</label>
                                                                        <input type="text" class="form-control" id="edit_phone_2{{ $contact->id }}" name="phone_2" 
                                                                               value="{{ old('phone_2', $contact->phone_2) }}">
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="edit_notes{{ $contact->id }}" class="form-label">Anotações</label>
                                                                    <textarea class="form-control" id="edit_notes{{ $contact->id }}" name="notes" rows="3">{{ old('notes', $contact->notes) }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="bx bx-save me-1"></i>Salvar
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-2">Nenhum contato cadastrado.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Tab: Criar Categoria -->
                    <div class="tab-pane fade" id="criar-categoria" role="tabpanel">
                        <div class="p-4">
                            <form action="{{ route('financial.contacts.categories.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="category_name" class="form-label">Nome da categoria <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="category_name" name="name" value="{{ old('name') }}" 
                                           placeholder="Digite o nome da categoria" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-check me-1"></i>Criar Categoria
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel Direito: Criar Contato -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header text-white" style="background-color: #20c997;">
                <h5 class="card-title mb-0">
                    <i class="bx bx-plus me-2"></i>+ Criar contato
                </h5>
            </header>
            <div class="card-body">
                <form action="{{ route('financial.contacts.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Digite o nome" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="Digite o e-mail">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria</label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                            <option value="">Nenhum</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="pessoa_fisica" {{ old('type', 'pessoa_fisica') == 'pessoa_fisica' ? 'selected' : '' }}>Pessoa física</option>
                            <option value="pessoa_juridica" {{ old('type') == 'pessoa_juridica' ? 'selected' : '' }}>Pessoa jurídica</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone_1" class="form-label">Telefone 1</label>
                        <input type="text" class="form-control @error('phone_1') is-invalid @enderror" 
                               id="phone_1" name="phone_1" value="{{ old('phone_1') }}" 
                               placeholder="Digite o telefone">
                        @error('phone_1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone_2" class="form-label">Telefone 2</label>
                        <input type="text" class="form-control @error('phone_2') is-invalid @enderror" 
                               id="phone_2" name="phone_2" value="{{ old('phone_2') }}" 
                               placeholder="Digite o telefone">
                        @error('phone_2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Anotações</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" name="notes" rows="4" 
                                  placeholder="Digite anotações (opcional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn w-100 text-white" style="background-color: #20c997;">
                        <i class="bx bx-check me-1"></i>Criar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
