/* Application-owned interactions for imported, script-free layouts. */
document.addEventListener('DOMContentLoaded', () => {
    const menu = document.querySelector('.menu-toggle');
    const navigation = document.getElementById(menu?.getAttribute('aria-controls') || 'navegacao');
    if (menu && navigation) {
        const close = () => {menu.setAttribute('aria-expanded', 'false'); navigation.classList.remove('open'); menu.textContent = 'Menu +';};
        menu.addEventListener('click', () => {const open = menu.getAttribute('aria-expanded') !== 'true';menu.setAttribute('aria-expanded', String(open));navigation.classList.toggle('open', open);menu.textContent = open ? 'Fechar −' : 'Menu +';});
        navigation.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
        document.addEventListener('keydown', event => {if(event.key === 'Escape') close();});
    }
    const share = document.getElementById('compartilhar');
    if (share) share.addEventListener('click', async () => {
        const data = {title: window.eventLandingData?.title || document.title, url: location.href.split('#')[0]};
        const status = document.getElementById('share-status');
        try {
            if (navigator.share) await navigator.share(data);
            else {await navigator.clipboard.writeText(data.url);if(status) status.textContent = 'Link copiado!';}
        } catch (error) {if(error.name !== 'AbortError' && status) status.textContent = 'Copie o endereço desta página para compartilhar.';}
    });
    document.querySelectorAll('[role="tablist"]').forEach(list => {
        const tabs = Array.from(list.querySelectorAll('[role="tab"]'));
        const select = tab => tabs.forEach(item => {const active = item === tab; item.setAttribute('aria-selected', String(active));item.tabIndex = active ? 0 : -1;const panel = document.getElementById(item.getAttribute('aria-controls'));if(panel) panel.hidden = !active;});
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(tab));
            tab.addEventListener('keydown', event => {let next;if(event.key === 'ArrowRight') next = (index+1)%tabs.length;else if(event.key === 'ArrowLeft') next = (index-1+tabs.length)%tabs.length;else return;event.preventDefault();select(tabs[next]);tabs[next].focus();});
        });
    });
});
