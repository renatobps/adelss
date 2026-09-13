<div class="mb-3" data-individual-message-wrap>
    <label class="form-label" for="{{ $idPrefix }}_message_individual">Mensagem individual</label>
    <textarea class="form-control" id="{{ $idPrefix }}_message_individual" name="mensagem_individual" rows="8"
              placeholder="Mensagem enviada para cada pessoa">{{ $individualTemplate }}</textarea>
    <small class="text-muted">
        Variáveis: <code>{nome}</code>, <code>{culto}</code>, <code>{dia_culto}</code>, <code>{hora_culto}</code>, <code>{area_servico}</code>, <code>{local_servico}</code>, <code>{responsavel_area}</code>
    </small>
</div>

<div class="mb-3" data-group-message-wrap>
    <label class="form-label" for="{{ $idPrefix }}_message_group">Mensagem do grupo</label>
    <textarea class="form-control" id="{{ $idPrefix }}_message_group" name="mensagem_grupo" rows="6"
              placeholder="Mensagem enviada nos grupos">{{ $groupTemplate }}</textarea>
    <small class="text-muted">
        Variáveis: <code>{culto}</code>, <code>{dia_culto}</code>, <code>{hora_culto}</code>, <code>{local_servico}</code>, <code>{responsavel_area}</code>
    </small>
</div>
