@php
    $menuId = 'member-actions-' . $member->id . '-' . ($suffix ?? 'main');
    $canMessage = filled($member->phone)
        && (auth()->user()?->is_admin || auth()->user()?->can('notificacoes.view') || auth()->user()?->hasPermission('notificacoes.manage'));
@endphp
<div class="dropdown members-actions">
    <button type="button" class="members-actions-btn" id="{{ $menuId }}"
            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Ações">
        <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $menuId }}">
        <li>
            <a class="dropdown-item" href="{{ route('members.show', $member) }}">
                <i class="bx bx-show me-2"></i> Ver detalhes
            </a>
        </li>
        @if($canEditMembers ?? false)
            <li>
                <a class="dropdown-item" href="{{ route('members.edit', $member) }}">
                    <i class="bx bx-edit me-2"></i> Editar
                </a>
            </li>
        @endif
        @if($canMessage)
            <li>
                <a class="dropdown-item" href="{{ route('notificacoes.painel.index', ['member_id' => $member->id]) }}">
                    <i class="bx bxl-whatsapp me-2"></i> Mensagem ao membro
                </a>
            </li>
        @endif
        @if($canDeleteMembers ?? false)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="{{ route('members.destroy', $member) }}" method="POST"
                      onsubmit="return confirm('Tem certeza que deseja excluir este membro?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bx bx-trash me-2"></i> Excluir
                    </button>
                </form>
            </li>
        @endif
    </ul>
</div>
