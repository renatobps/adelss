{{-- Sorteio entre inscritos --}}
<div class="modal fade" id="erRaffleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content er-raffle-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bx bx-dice-5 text-warning me-1"></i> Sorteio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div class="er-raffle-scope mb-3" role="group" aria-label="Quem entra no sorteio">
                    <input type="radio" class="btn-check" name="erRaffleScope" id="erRaffleScopeAll" value="todos" checked>
                    <label class="btn btn-outline-secondary btn-sm" for="erRaffleScopeAll">
                        Todos <span class="er-muted" id="erRaffleCountAll">({{ $raffleEntries->count() }})</span>
                    </label>
                    <input type="radio" class="btn-check" name="erRaffleScope" id="erRaffleScopePresent" value="presentes"
                           @disabled($raffleEntries->where('presente', true)->isEmpty())>
                    <label class="btn btn-outline-secondary btn-sm" for="erRaffleScopePresent">
                        Somente presentes <span class="er-muted" id="erRaffleCountPresent">({{ $raffleEntries->where('presente', true)->count() }})</span>
                    </label>
                </div>
                <p class="text-muted small mb-3" id="erRaffleHint">O sorteio inclui inscritos pendentes e confirmados.</p>
                <div class="er-raffle-stage" id="erRaffleStage">
                    <div class="er-raffle-glow" aria-hidden="true"></div>
                    <div class="er-raffle-name" id="erRaffleName">Pronto para sortear</div>
                </div>
                <p class="er-raffle-sub small text-muted mt-3 mb-0" id="erRaffleSub">O nome do ganhador aparece após a animação.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-warning" id="erRaffleStart">
                    <i class="bx bx-play-circle"></i> Sortear agora
                </button>
            </div>
        </div>
    </div>
</div>

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

@if($canEditRegistrations)
    {{-- WhatsApp livre (texto + mídia) para inscritos selecionados --}}
    <div class="modal fade" id="erWhatsappModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <form method="post" action="{{ route('agenda.eventos.registrations.whatsapp', $event) }}"
                  enctype="multipart/form-data" id="erWhatsappForm" class="modal-content">
                @csrf
                <div id="erWhatsappIds"></div>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bxl-whatsapp text-success me-1"></i> WhatsApp aos inscritos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="erWhatsappMensagem">Mensagem / legenda</label>
                        <textarea name="mensagem" id="erWhatsappMensagem" class="form-control" rows="4" maxlength="4096"
                                  placeholder="Olá {nome}!&#10;&#10;Passando para lembrar...">{{ old('mensagem') }}</textarea>
                        <small class="text-muted">Use <code>{nome}</code> para o primeiro nome. Texto ou arquivo — pelo menos um dos dois.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivo de mídia</label>
                        <div class="er-dropzone" id="erDropzone">
                            <input type="file" name="arquivo" id="erWhatsappArquivo" class="d-none"
                                   accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            <div class="er-dropzone__idle">
                                <i class="bx bx-cloud-upload fs-2 text-primary"></i>
                                <div class="fw-semibold">Arraste um arquivo ou clique para enviar</div>
                                <div class="small text-muted">Opcional · máx. 20 MB · imagem, vídeo, áudio ou documento</div>
                            </div>
                            <div class="er-dropzone__preview">
                                <div class="er-dropzone__thumb" id="erFileThumb"><i class="bx bx-file"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate" id="erFileName">—</div>
                                    <div class="small text-muted" id="erFileMeta">—</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="erFileClear" title="Remover">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label mb-0">Destinatários <span class="text-muted fw-normal" id="erWhatsappCount">(0)</span></label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="erWhatsappSelectAll">Selecionar todos</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="erWhatsappClear">Limpar</button>
                        </div>
                    </div>
                    <input type="search" class="form-control form-control-sm mb-2" id="erWhatsappSearch"
                           placeholder="Filtrar por nome ou telefone...">
                    <div class="er-whats-list" id="erWhatsappList"></div>
                    <p class="small text-muted mb-0 mt-2">
                        O envio roda em segundo plano: você volta para as inscrições na hora.
                        Sucessos e erros ficam em Notificações. Os envios continuam espaçados (8–20 s) para proteger o número.
                        Limite de {{ $whatsappBatchLimit }} por vez. Inscritos sem telefone não aparecem na lista.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm" id="erWhatsappSubmit">
                        <i class="bx bxl-whatsapp"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
