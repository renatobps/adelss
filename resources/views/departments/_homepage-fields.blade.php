<div class="col-12 mb-3">
    <hr class="my-2">
    <h5 class="mb-3"><i class="bx bx-globe me-1"></i> Página Principal</h5>
</div>

<div class="col-md-12 mb-3">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="show_on_homepage" name="show_on_homepage" value="1"
               {{ old('show_on_homepage', $department->show_on_homepage ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="show_on_homepage">Exibir na Página Principal</label>
    </div>
    <small class="form-text text-muted">Quando ativo, este departamento aparece como card na landing pública.</small>
</div>

<div class="col-md-6 mb-3 homepage-order-field" style="{{ old('show_on_homepage', $department->show_on_homepage ?? false) ? '' : 'display:none;' }}">
    <label for="homepage_order" class="form-label">Ordem na home</label>
    <input type="number" class="form-control @error('homepage_order') is-invalid @enderror"
           id="homepage_order" name="homepage_order" min="0" max="999"
           value="{{ old('homepage_order', $department->homepage_order ?? '') }}"
           placeholder="Ex: 1">
    @error('homepage_order')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 mb-3 homepage-url-field" style="{{ old('show_on_homepage', $department->show_on_homepage ?? false) ? '' : 'display:none;' }}">
    <label for="homepage_url" class="form-label">Link "Saiba mais" (opcional)</label>
    <input type="url" class="form-control @error('homepage_url') is-invalid @enderror"
           id="homepage_url" name="homepage_url"
           value="{{ old('homepage_url', $department->homepage_url ?? '') }}"
           placeholder="https://...">
    @error('homepage_url')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('show_on_homepage');
    const orderField = document.querySelector('.homepage-order-field');
    const urlField = document.querySelector('.homepage-url-field');

    if (!toggle) return;

    toggle.addEventListener('change', function () {
        const show = toggle.checked;
        if (orderField) orderField.style.display = show ? '' : 'none';
        if (urlField) urlField.style.display = show ? '' : 'none';
    });
});
</script>
@endpush
