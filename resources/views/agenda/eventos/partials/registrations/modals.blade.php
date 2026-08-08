{{-- Detalhes da inscrição --}}
<div class="modal fade" id="erDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da inscrição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="erDetailsBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Fechar</button>
                <a href="#" class="btn btn-outline-secondary btn-sm" id="erDetailsPdf" target="_blank">
                    <i class="bx bxs-file-pdf"></i> Baixar PDF
                </a>
            </div>
        </div>
    </div>
</div>

@if($canEditRegistrations)
    {{-- Edição dos dados do inscrito --}}
    <div class="modal fade" id="erEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" id="erEditForm" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Editar inscrição <span id="erEditNumber" class="text-muted small"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="erEditName">Nome *</label>
                        <input type="text" class="form-control" id="erEditName" name="name" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="erEditEmail">E-mail</label>
                        <input type="email" class="form-control" id="erEditEmail" name="email" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="erEditPhone">Telefone</label>
                        <input type="text" class="form-control js-er-phone-mask" id="erEditPhone" name="phone"
                               placeholder="(99) 99999-9999" maxlength="15">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="erEditAddress">Endereço</label>
                        <input type="text" class="form-control" id="erEditAddress" name="address" maxlength="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Confirmação de mudança de status --}}
    <div class="modal fade" id="erStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="post" id="erStatusForm" class="modal-content">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" id="erStatusValue">
                <div class="modal-header">
                    <h5 class="modal-title">Alterar status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1" id="erStatusText"></p>
                    <p class="small text-muted mb-0" id="erStatusHint"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($canDeleteRegistrations)
    {{-- Exclusão individual --}}
    <div class="modal fade" id="erDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" id="erDeleteForm" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Excluir inscrição</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Confirme que é esta a inscrição a excluir:</p>
                    <div class="border rounded p-2 mb-3" id="erDeleteSummary"></div>
                    <div class="alert alert-warning mb-0 small" id="erDeleteWarning">
                        A inscrição sai da lista mas continua recuperável pelo filtro <strong>Excluídas</strong>.
                        Se a pessoa se inscreveu e desistiu, prefira <strong>cancelar</strong> — o histórico é preservado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="erDeleteConfirm">Excluir inscrição</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Exclusão em lote --}}
    <div class="modal fade" id="erBulkDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Excluir selecionadas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="erBulkDeleteCount"></p>
                    <ul class="small mb-0" id="erBulkDeleteList"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm" id="erBulkDeleteConfirm">Excluir</button>
                </div>
            </div>
        </div>
    </div>
@endif
