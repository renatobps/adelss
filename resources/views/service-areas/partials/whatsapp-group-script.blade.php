@php $area = $area ?? null; @endphp
@push('scripts')
<script>
(function () {
    const select = document.getElementById('whatsapp_group_jid');
    const nameInput = document.getElementById('whatsapp_group_name');
    const refreshBtn = document.getElementById('whatsappGroupsRefresh');
    if (!select) return;

    const groupsUrl = @json(route('voluntarios.areas.whatsapp-grupos'));
    const savedJid = @json(old('whatsapp_group_jid', $area->whatsapp_group_jid ?? ''));
    const savedName = @json(old('whatsapp_group_name', $area->whatsapp_group_name ?? ''));
    const emptyLabel = select.options[0] ? select.options[0].textContent : 'Nenhum grupo';

    function syncGroupName() {
        if (!nameInput) return;
        const opt = select.selectedOptions[0];
        nameInput.value = opt && opt.value ? (opt.textContent || '').trim() : '';
    }

    async function loadGroups() {
        const previous = select.value || savedJid || '';
        select.innerHTML = '<option value="">Carregando grupos...</option>';
        select.disabled = true;
        try {
            const res = await fetch(groupsUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            select.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = emptyLabel;
            select.appendChild(empty);

            (data.groups || []).forEach(function (group) {
                const opt = document.createElement('option');
                opt.value = group.jid;
                opt.textContent = group.name || group.jid;
                if (previous && previous === group.jid) opt.selected = true;
                select.appendChild(opt);
            });

            if (previous && !select.value) {
                const opt = document.createElement('option');
                opt.value = previous;
                opt.textContent = savedName || previous;
                opt.selected = true;
                select.appendChild(opt);
            }

            if (!(data.success) && (data.message || data.error) && !(data.groups || []).length) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = data.message || data.error;
                select.appendChild(opt);
            }

            syncGroupName();
        } catch (e) {
            select.innerHTML = '<option value="">Falha ao carregar grupos</option>';
        } finally {
            select.disabled = false;
        }
    }

    select.addEventListener('change', syncGroupName);
    refreshBtn?.addEventListener('click', loadGroups);
    loadGroups();
})();
</script>
@endpush
