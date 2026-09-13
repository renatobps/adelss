<div class="col-md-6 mb-3">
    <label for="whatsapp_group_jid" class="form-label">Grupo de WhatsApp</label>
    <div class="input-group">
        <select class="form-select @error('whatsapp_group_jid') is-invalid @enderror"
                id="whatsapp_group_jid"
                name="whatsapp_group_jid">
            <option value="">{{ !empty($area?->parent_id) ? 'Herdar o grupo da área pai' : 'Nenhum grupo (não envia PDF)' }}</option>
        </select>
        <button type="button" class="btn btn-outline-secondary" id="whatsappGroupsRefresh" title="Atualizar lista">
            <i class="bx bx-refresh"></i>
        </button>
    </div>
    <input type="hidden" name="whatsapp_group_name" id="whatsapp_group_name"
           value="{{ old('whatsapp_group_name', $area->whatsapp_group_name ?? '') }}">
    <small class="form-text text-muted">
        A escala desta área (e das subáreas, se não tiverem grupo próprio) vai para esse grupo
        na publicação e toda segunda-feira.
        Ex.: Intercessão, Voluntários, Porteiro, Líderes e obreiros.
    </small>
    @error('whatsapp_group_jid')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
