document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-event-banner]').forEach(container => {
        const input = container.querySelector('input[type="file"]');
        const remove = container.querySelector('#remove_banner');
        const preview = container.querySelector('[data-banner-preview]');
        const image = preview.querySelector('img');
        const caption = container.querySelector('[data-banner-caption]');
        const error = container.querySelector('[data-banner-error]');
        let objectUrl = null;
        const render = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            const file = input.files[0];
            const source = file ? (objectUrl = URL.createObjectURL(file)) : (remove?.checked ? '' : image.dataset.original);
            if (source) image.src = source;
            else image.removeAttribute('src');
            preview.hidden = !source;
            caption.textContent = file ? 'Prévia do novo banner' : 'Banner atual';
        };
        input.addEventListener('change', () => {
            const file = input.files[0];
            error.hidden = true;
            if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
                input.value = '';
                error.textContent = 'Escolha uma imagem JPG, PNG ou WebP de até 5 MB.';
                error.hidden = false;
            } else if (file && remove) {
                remove.checked = false;
            }
            render();
        });
        remove?.addEventListener('change', () => {
            if (remove.checked) input.value = '';
            render();
        });
        window.addEventListener('pagehide', () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
        });
        render();
    });
});
