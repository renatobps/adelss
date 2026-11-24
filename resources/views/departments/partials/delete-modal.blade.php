<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title h5" id="{{ $modalId }}-title">{{ $deleteTitle }}</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">{{ $deleteMessage }}</div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
            <form action="{{ $deleteUrl }}" method="POST">
                @csrf @method('DELETE')
                <button class="btn btn-danger" type="submit">Confirmar exclusão</button>
            </form>
        </div>
    </div></div>
</div>
