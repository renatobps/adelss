<!-- Modal Frequência Mensal -->
<div class="modal fade" id="frequencyMonthlyModal" tabindex="-1" aria-labelledby="frequencyMonthlyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="frequencyMonthlyModalLabel">Frequência mensal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ensino.turmas.reports.frequency-monthly', $turma) }}" method="GET" id="frequencyForm">
                <input type="hidden" id="frequency_discipline_id" name="discipline_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Período:</label>
                        <div class="row">
                            <div class="col-md-6">
                                <select class="form-select" id="frequency_month" name="month" required>
                                    @php
                                        $months = [
                                            1 => 'Janeiro',
                                            2 => 'Fevereiro',
                                            3 => 'Março',
                                            4 => 'Abril',
                                            5 => 'Maio',
                                            6 => 'Junho',
                                            7 => 'Julho',
                                            8 => 'Agosto',
                                            9 => 'Setembro',
                                            10 => 'Outubro',
                                            11 => 'Novembro',
                                            12 => 'Dezembro'
                                        ];
                                    @endphp
                                    @foreach($months as $num => $name)
                                        <option value="{{ $num }}" {{ old('month', now()->month) == $num ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="number" class="form-control" id="frequency_year" name="year" 
                                       value="{{ old('year', now()->year) }}" min="2020" max="2100" required>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mb-3">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-check me-1"></i>Gerar relatório
                        </button>
                        <button type="button" class="btn btn-primary" onclick="printFrequency()">
                            <i class="bx bx-printer me-1"></i>Imprimir
                        </button>
                    </div>
                    <div id="frequency_table_container">
                        <!-- Tabela de frequência será gerada aqui via JavaScript/AJAX -->
                        <p class="text-muted text-center">Selecione o período e clique em "Gerar relatório" para visualizar a frequência.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </form>
        </div>
    </div>
</div>


