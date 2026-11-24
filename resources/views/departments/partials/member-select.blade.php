<label for="{{ $id }}-search" class="form-label">Buscar membro responsável</label>
<input id="{{ $id }}-search" type="search" class="form-control mb-2" placeholder="Digite o nome do membro..." data-member-select="{{ $id }}" autocomplete="off">
<label for="{{ $id }}" class="form-label">Membro responsável</label>
<select id="{{ $id }}" name="responsible_member_id" class="form-select">
    <option value="">Sem responsável definido</option>
    @if($selected && !$members->contains('id', $selected))
        <option value="{{ $selected }}" selected>Membro indisponível (#{{ $selected }}) — escolha outro ou remova o responsável</option>
    @endif
    @foreach($members as $member)
        <option value="{{ $member->id }}" @selected((string) $selected === (string) $member->id)>{{ $member->name }}</option>
    @endforeach
</select>
<p class="form-text mb-0">Escolha no cadastro geral de membros. O responsável será incluído entre os participantes, preservando os vínculos existentes.</p>
