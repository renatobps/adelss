document.addEventListener('DOMContentLoaded', function () {
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    document.querySelectorAll('[data-people-picker]').forEach(picker => {
        const choices = Array.from(picker.querySelectorAll('[data-person-choice]'));
        const selectedList = picker.querySelector('[data-people-selection]');
        const template = picker.querySelector('[data-person-template]');
        const search = picker.querySelector('[data-people-search]');
        const selectedOnly = picker.querySelector('[data-people-selected-only]');
        const filter = () => {
            const term = normalize(search.value);
            let count = 0;
            choices.forEach(choice => {
                const visible = normalize(choice.dataset.personName).includes(term) && (!selectedOnly.checked || choice.checked);
                choice.closest('[data-person-option]').hidden = !visible;
                if (visible) count++;
            });
            picker.querySelector('[data-people-results]').textContent = `${count} ${count === 1 ? 'pessoa encontrada' : 'pessoas encontradas'}`;
            picker.querySelector('[data-people-no-results]').hidden = count !== 0;
        };
        const render = () => {
            const checked = choices.filter(choice => choice.checked);
            const fragment = document.createDocumentFragment();
            checked.forEach(choice => {
                const card = template.content.cloneNode(true);
                const name = choice.dataset.personName;
                card.querySelector('[data-person-label]').textContent = name;
                card.querySelector('[data-person-initials]').textContent = name.trim().split(/\s+/).slice(0, 2).map(part => Array.from(part)[0]).join('').toLocaleUpperCase('pt-BR');
                const remove = card.querySelector('[data-person-remove]');
                remove.dataset.personId = choice.value;
                remove.setAttribute('aria-label', `Remover ${name} da seleção`);
                remove.hidden = choice.disabled;
                card.querySelector('[data-person-lock-label]').hidden = !choice.disabled;
                fragment.appendChild(card);
            });
            selectedList.replaceChildren(fragment);
            picker.querySelector('[data-people-count]').textContent = `${checked.length} ${checked.length === 1 ? 'selecionado' : 'selecionados'}`;
            picker.querySelector('[data-people-empty]').hidden = checked.length !== 0;
            filter();
        };
        picker.addEventListener('change', event => {
            if (event.target.matches('[data-person-choice]')) render();
            if (event.target === selectedOnly) filter();
        });
        picker.addEventListener('click', event => {
            const button = event.target.closest('[data-person-remove]');
            if (!button) return;
            const choice = choices.find(item => item.value === button.dataset.personId);
            if (!choice || choice.disabled) return;
            choice.checked = false;
            choice.dispatchEvent(new Event('change', { bubbles: true }));
        });
        search.addEventListener('input', filter);
        picker.querySelector('details').addEventListener('toggle', event => {
            if (event.target.open) search.focus({ preventScroll: true });
        });
        render();
    });
    document.querySelectorAll('[data-filter-list]').forEach(container => {
        const input = container.querySelector('[data-filter-input]');
        const rows = Array.from(container.querySelectorAll('[data-filter-item]'));
        if (!input) return;
        input.addEventListener('input', () => {
            const term = normalize(input.value);
            let visible = 0;
            rows.forEach(row => { row.hidden = !normalize(row.textContent).includes(term); if (!row.hidden) visible++; });
            const empty = container.querySelector('[data-filter-empty]');
            if (empty) empty.hidden = visible !== 0;
            const count = container.querySelector('[data-filter-count]');
            if (count) count.textContent = `${visible} de ${rows.length} pessoas`;
        });
    });
    const form = document.getElementById('departmentForm');
    if (!form) return;
    let dirty = false;
    let submitting = false;
    form.addEventListener('change', event => {
        if (!event.target.name) return;
        dirty = true;
        const note = form.querySelector('[data-dirty-note]');
        if (note) note.hidden = false;
    });
    form.addEventListener('input', event => {
        if (!event.target.name) return;
        dirty = true;
        const note = form.querySelector('[data-dirty-note]');
        if (note) note.hidden = false;
    });
    form.addEventListener('submit', () => { submitting = true; });
    window.addEventListener('beforeunload', event => {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });
    const name = form.querySelector('[name=name]');
    if (name) name.addEventListener('input', () => {
        const title = document.querySelector('[data-department-title]');
        title.textContent = name.value.trim() || title.dataset.titleDefault || 'Novo departamento';
    });
    const color = form.querySelector('[name=color]');
    if (color) color.addEventListener('input', () => { document.querySelector('.dept-page').style.setProperty('--dept-accent', color.value); });
    const icon = form.querySelector('[name=icon]');
    if (icon) icon.addEventListener('change', () => {
        const preview = document.querySelector('[data-department-icon]');
        if (preview) preview.className = icon.value.startsWith('bx-') ? `bx ${icon.value}` : icon.value;
    });
    const logo = form.querySelector('[name=logo]');
    let previewUrl;
    if (logo) logo.addEventListener('change', () => {
        const image = document.querySelector('[data-department-logo]');
        const glyph = document.querySelector('[data-department-icon]');
        const file = logo.files[0];
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        logo.setCustomValidity(file && file.size > 2 * 1024 * 1024 ? 'Escolha uma imagem com até 2 MB.' : '');
        if (file && logo.validity.valid) {
            previewUrl = URL.createObjectURL(file);
            image.src = previewUrl; image.hidden = false; glyph.hidden = true;
        } else {
            const original = image.dataset.original;
            if (original) image.src = original;
            image.hidden = !original; glyph.hidden = Boolean(original);
        }
    });
});
