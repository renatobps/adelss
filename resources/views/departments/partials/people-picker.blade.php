@php
    $visibleSelectedCount = $members->whereIn('id', $selectedIds)->count();
    $fieldName = $fieldName ?? $kind;
@endphp
<div data-people-picker data-kind="{{ $kind }}">
    <div class="dept-panel-heading">
        <div><h2>{{ $pickerTitle }}</h2><p>{{ $pickerDescription }}</p></div>
        <span class="dept-count" data-people-count aria-live="polite">{{ $visibleSelectedCount }} {{ $visibleSelectedCount === 1 ? 'selecionado' : 'selecionados' }}</span>
    </div>
    <div class="dept-panel-body">
        <div class="dept-person-list dept-person-list--grid dept-scroll-list" data-people-selection>
            @foreach($members->whereIn('id', $selectedIds) as $person)
                <div class="dept-person">
                    <span class="dept-avatar dept-avatar--small" aria-hidden="true">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                    <span class="dept-person-name">{{ $person->name }}</span>
                </div>
            @endforeach
        </div>
        <div class="dept-empty" data-people-empty @if($visibleSelectedCount) hidden @endif><i class="bx bx-user-plus" aria-hidden="true"></i>{{ $emptyText }}</div>
        <details class="dept-picker" @if(!$visibleSelectedCount) open @endif>
            <summary>{{ $pickerAction }}</summary>
            <div class="dept-picker-content">
                <label class="visually-hidden" for="{{ $kind }}-search">Buscar no cadastro de membros</label>
                <div class="dept-search"><i class="bx bx-search" aria-hidden="true"></i><input id="{{ $kind }}-search" type="search" class="form-control" placeholder="Buscar pelo nome..." autocomplete="off" data-people-search></div>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                    <small class="dept-muted" data-people-results aria-live="polite">{{ $members->count() }} pessoas no cadastro</small>
                    <label class="dept-muted d-inline-flex gap-2 align-items-center"><input type="checkbox" data-people-selected-only> Apenas selecionados</label>
                </div>
                <div class="dept-choice-list">
                    @foreach($members as $person)
                        @php
                            $locked = in_array((int) $person->id, $protectedIds, true);
                        @endphp
                        <label class="dept-choice" data-person-option>
                            <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $person->id }}" data-person-choice data-person-name="{{ $person->name }}" @checked(in_array((int) $person->id, $selectedIds, true)) @disabled($locked) @if($locked) data-person-locked="true" @endif>
                            <span class="dept-choice-name">{{ $person->name }}</span>
                            @if($locked)<span class="dept-tag">Responsável</span>@endif
                        </label>
                    @endforeach
                </div>
                <p class="dept-muted mt-3 mb-0" data-people-no-results hidden>Nenhuma pessoa encontrada. Tente outro nome.</p>
            </div>
        </details>
        @if(count($protectedIds))
            <div class="dept-help"><i class="bx bx-lock-alt" aria-hidden="true"></i><span>Responsáveis por cargos permanecem na equipe. Para retirar essa atribuição, acesse Administração.</span></div>
        @endif
    </div>
    <template data-person-template>
        <div class="dept-person">
            <span class="dept-avatar dept-avatar--small" data-person-initials aria-hidden="true"></span>
            <div class="dept-person-info"><span class="dept-person-name" data-person-label></span><span class="dept-person-meta" data-person-lock-label hidden>Responsável por cargo</span></div>
            <button type="button" class="dept-icon-button" data-person-remove><i class="bx bx-x" aria-hidden="true"></i></button>
        </div>
    </template>
</div>
