const test = require('node:test');
const assert = require('node:assert/strict');
const ui = require('../../public/js/agenda-calendar-utils.js');

const events = [
    { id: 1, title: 'Culto', category_id: 2, start: '2027-01-10T19:00:00', location: 'Templo' },
    { id: 2, title: 'Encontro livre', category_id: null, start: '2027-01-11T19:00:00' },
    { id: 'department-event:1', title: 'Reunião — Jovens', start: '2027-01-12', end: '2027-01-13', extendedProps: { sourceType: 'department', departmentName: 'Jovens', location: 'Salão' } },
];
const filters = { source: 'all', search: '', categories: ['2', 'none'] };

test('source and category filters keep native and department events distinct', () => {
    assert.equal(ui.filterEvents(events, filters).length, 3);
    assert.deepEqual(ui.filterEvents(events, { ...filters, source: 'department', categories: [] }).map(e => e.id), ['department-event:1']);
    assert.deepEqual(ui.filterEvents(events, { ...filters, source: 'agenda', categories: ['none'] }).map(e => e.id), [2]);
    assert.deepEqual(ui.filterEvents(events, { ...filters, categories: [] }).map(e => e.id), ['department-event:1']);
});

test('search handles accents, case, location, department and FullCalendar extended properties', () => {
    assert.deepEqual(ui.filterEvents(events, { ...filters, search: '  SALAO ' }).map(e => e.id), ['department-event:1']);
    assert.deepEqual(ui.filterEvents(events, { ...filters, search: 'jovens' }).map(e => e.id), ['department-event:1']);
    assert.equal(ui.filterEvents([{ title: 'Culto', extendedProps: { category_id: 2, location: 'Sede' } }], { ...filters, search: 'sede' }).length, 1);
    assert.equal(ui.filterEvents(events, { ...filters, search: '<script>' }).length, 0);
});

test('period totals exclude adjacent days and honor all-day exclusive endings', () => {
    const start = new Date(2027, 0, 1); const end = new Date(2027, 1, 1);
    assert.equal(ui.inPeriod({ start: '2026-12-31', end: '2027-01-01' }, start, end), false);
    assert.equal(ui.inPeriod({ start: '2027-02-01' }, start, end), false);
    assert.equal(ui.inPeriod({ start: '2026-12-31', end: '2027-01-02' }, start, end), true);
    assert.equal(ui.inPeriod({ start: '2027-01-01' }, start, end), true);
});

test('local date formatting does not shift a day when converting midnight to UTC', () => {
    assert.equal(ui.localDate(new Date(2027, 0, 1, 23, 30)), '2027-01-01');
});

test('event text contrasts with pale and dark category colors', () => {
    assert.equal(ui.textColor('#edffa0'), '#111111');
    assert.equal(ui.textColor('#123456'), '#ffffff');
    assert.equal(ui.textColor('invalid'), '#ffffff');
});
