<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content financial-account-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title mb-1" id="transferModalLabel">Transferir entre contas</h5>
                    <p class="text-muted small mb-0">
                        Use quando o dinheiro sai de uma conta e entra em outra, como o depósito do caixa na Mercado Pago.
                        Não entra como receita nem como despesa.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('financial.transfers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="transfer_from_account" class="form-label">De (saiu de) <span class="text-danger">*</span></label>
                        <select class="form-select @error('from_account_id') is-invalid @enderror"
                                id="transfer_from_account" name="from_account_id" required>
                            <option value="">Selecione</option>
                            @foreach($transferAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->paymentOptionLabel() }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="transfer_to_account" class="form-label">Para (entrou em) <span class="text-danger">*</span></label>
                        <select class="form-select @error('to_account_id') is-invalid @enderror"
                                id="transfer_to_account" name="to_account_id" required>
                            <option value="">Selecione</option>
                            @foreach($transferAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->paymentOptionLabel() }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="transfer_amount" class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   id="transfer_amount" name="amount" value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="transfer_date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control @error('transfer_date') is-invalid @enderror"
                                   id="transfer_date" name="transfer_date"
                                   value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required>
                            @error('transfer_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="transfer_notes" class="form-label">Observação</label>
                        <textarea class="form-control" id="transfer_notes" name="notes" rows="2"
                                  placeholder="Ex: depósito do caixa da semana">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar transferência</button>
                </div>
            </form>
        </div>
    </div>
</div>
