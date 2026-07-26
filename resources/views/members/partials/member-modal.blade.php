{{-- Modal em formato de card: dados do membro + ações no rodapé --}}
<div class="modal fade" id="memberQuickModal" tabindex="-1" aria-labelledby="memberQuickModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content members-quick-modal border-0 shadow">
            <button type="button" class="btn-close members-quick-modal__close" data-bs-dismiss="modal" aria-label="Fechar"></button>

            <div class="modal-body p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div id="mqAvatar" class="flex-shrink-0"></div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="mb-1" id="memberQuickModalLabel">
                            <span id="mqName" class="text-break"></span>
                        </h5>
                        <div id="mqEmail" class="text-muted small text-break"></div>
                        <div id="mqPhone" class="text-muted small"></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-1 mb-3" id="mqBadges"></div>

                <div class="members-quick-modal__info">
                    <div class="members-quick-modal__row">
                        <span class="text-muted">Status</span>
                        <strong id="mqStatus">—</strong>
                    </div>
                    <div class="members-quick-modal__row">
                        <span class="text-muted">Cargo</span>
                        <strong id="mqRole">—</strong>
                    </div>
                    <div class="members-quick-modal__row">
                        <span class="text-muted">Departamentos</span>
                        <strong id="mqDepartments">—</strong>
                    </div>
                    <div class="members-quick-modal__row">
                        <span class="text-muted">PGI</span>
                        <strong id="mqPgi">—</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer members-quick-modal__actions flex-wrap justify-content-stretch gap-2 border-top px-4 py-3">
                <a href="#" id="mqProfileBtn" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-user me-1"></i> Ver perfil do membro
                </a>
                <a href="#" id="mqEditBtn" class="btn btn-outline-secondary d-none">
                    <i class="bx bx-edit me-1"></i> Editar
                </a>
                <a href="#" id="mqMessageBtn" class="btn btn-outline-success d-none">
                    <i class="bx bxl-whatsapp me-1"></i> Mensagem
                </a>
                <form id="mqDeleteForm" method="POST" class="d-none"
                      onsubmit="return confirm('Tem certeza que deseja excluir este membro?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bx bx-trash me-1"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
