(function (root) {
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
    const details = event => event.extendedProps || event;
    const isDepartment = event => details(event).sourceType === 'department';
    const filterEvents = (events, filters) => events.filter(event => {
        const props = details(event);
        const department = isDepartment(event);
        if (filters.source === 'department' && !department) return false;
        if (filters.source === 'agenda' && department) return false;
        if (!department && !filters.categories.includes(String(props.category_id || 'none'))) return false;
        const text = [event.title, props.departmentName, props.location, props.description].join(' ');
        return normalize(text).includes(normalize(filters.search).trim());
    });
    const localDate = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const inPeriod = (event, start, end) => {
        const from = new Date(event.start);
        const to = event.end ? new Date(event.end) : from;
        // Date-only values are local calendar days, not UTC instants.
        if (typeof event.start === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(event.start)) from.setTime(new Date(event.start + 'T00:00:00').getTime());
        if (typeof event.end === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(event.end)) to.setTime(new Date(event.end + 'T00:00:00').getTime());
        return from < end && (to > start || (to.getTime() === from.getTime() && from >= start));
    };
    const textColor = hex => {
        if (!/^#[0-9a-f]{6}$/i.test(hex || '')) return '#ffffff';
        const channels = hex.slice(1).match(/../g).map(channel => parseInt(channel, 16) / 255).map(c => c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4);
        const luminance = channels[0] * .2126 + channels[1] * .7152 + channels[2] * .0722;
        return luminance > .179 ? '#111111' : '#ffffff';
    };
    const api = { normalize, filterEvents, localDate, inPeriod, textColor, isDepartment };
    root.AdelAgenda = api;
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
})(typeof window !== 'undefined' ? window : globalThis);
