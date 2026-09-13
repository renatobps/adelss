<div class="mb-3">
    <label class="form-label">Enviar para</label>
    <div class="border rounded p-3">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="1"
                   id="{{ $idPrefix }}_notify_groups" name="notify_groups" checked>
            <label class="form-check-label" for="{{ $idPrefix }}_notify_groups">
                <strong>Grupos de WhatsApp de cada área</strong>
                <div class="small text-muted mb-0">
                    Envia o PDF da área para o grupo configurado (Intercessão, Voluntários, Portaria, Preletores).
                </div>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1"
                   id="{{ $idPrefix }}_notify_individuals" name="notify_individuals" checked>
            <label class="form-check-label" for="{{ $idPrefix }}_notify_individuals">
                <strong>Individualmente para cada pessoa escalada</strong>
                <div class="small text-muted mb-0">
                    Envia a mensagem no WhatsApp de cada voluntário.
                </div>
            </label>
        </div>
    </div>
</div>
