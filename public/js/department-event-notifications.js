document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-event-notifications]');
    if (!root) return;
    const values = JSON.parse(root.dataset.previewValues);
    const message = root.querySelector('#notification-message');
    const preview = root.querySelector('[data-notification-preview]');
    const render = () => {
        const first = root.querySelector('[data-person-choice]:checked');
        const replacements = { ...values, '{nome}': first?.dataset.personName || 'Nome do participante' };
        preview.textContent = message.value.replace(/\{(?:nome|evento|departamento|data|horario|local)\}/g, key => replacements[key]);
    };
    message.addEventListener('input', render);
    root.addEventListener('change', render);
    root.querySelector('[data-select-team]').addEventListener('click', event => {
        const selected = new Set(JSON.parse(event.currentTarget.dataset.selectTeam).map(String));
        const choices = Array.from(root.querySelectorAll('[data-person-choice]'));
        choices.forEach(choice => { if (selected.has(choice.value)) choice.checked = true; });
        choices[0]?.dispatchEvent(new Event('change', { bubbles: true }));
        render();
    });
    const automatic = root.querySelector('#automatic-enabled');
    const schedule = root.querySelector('#notification-schedule');
    const updateRequired = () => { schedule.required = automatic.checked; };
    automatic.addEventListener('change', updateRequired);
    updateRequired();
    render();
});
