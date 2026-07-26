@extends('layouts.porto')

@section('title', 'Campos personalizados')
@section('page-title', 'Campos personalizados')

@section('breadcrumbs')
    <li><a href="{{ route('members.index') }}">Membros</a></li>
    <li><span>Campos</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Novo campo</h5>
                <form method="POST" action="{{ route('members.custom-fields.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control" required maxlength="120" placeholder="Ex: Instrumento que toca">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select" id="fieldType" required>
                            @foreach(\App\Models\MemberCustomField::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="optionsWrap" style="display:none">
                        <label class="form-label">Opções (uma por linha ou separadas por vírgula)</label>
                        <textarea name="options" class="form-control" rows="3" placeholder="Guitarra&#10;Teclado&#10;Bateria"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordem</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0" max="999">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="is_required">
                        <label class="form-check-label" for="is_required">Obrigatório</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Adicionar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Campos cadastrados</h5>
                @forelse($fields as $field)
                    <div class="border rounded p-3 mb-3">
                        <form method="POST" action="{{ route('members.custom-fields.update', $field) }}" class="mb-2">
                            @csrf
                            @method('PUT')
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small">Nome</label>
                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $field->name }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Tipo</label>
                                    <select name="type" class="form-select form-select-sm">
                                        @foreach(\App\Models\MemberCustomField::TYPES as $key => $label)
                                            <option value="{{ $key }}" @selected($field->type === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Ordem</label>
                                    <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $field->sort_order }}">
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req{{ $field->id }}" @checked($field->is_required)>
                                        <label class="form-check-label small" for="req{{ $field->id }}">Obrigatório</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act{{ $field->id }}" @checked($field->is_active)>
                                        <label class="form-check-label small" for="act{{ $field->id }}">Ativo</label>
                                    </div>
                                </div>
                                @if($field->type === 'select')
                                    <div class="col-12">
                                        <label class="form-label small">Opções</label>
                                        <textarea name="options" class="form-control form-control-sm" rows="2">{{ implode("\n", $field->options ?? []) }}</textarea>
                                    </div>
                                @else
                                    <input type="hidden" name="options" value="">
                                @endif
                                <div class="col-12">
                                    <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('members.custom-fields.destroy', $field) }}" onsubmit="return confirm('Remover este campo?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted mb-0">Nenhum campo personalizado ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('fieldType')?.addEventListener('change', function () {
    document.getElementById('optionsWrap').style.display = this.value === 'select' ? '' : 'none';
});
</script>
@endpush
