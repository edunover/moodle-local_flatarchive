const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const root = path.resolve(__dirname, '..');
const template = fs.readFileSync(path.join(root, 'templates/index.mustache'), 'utf8')
    .replaceAll('{{archiveurl}}', '/local/flatarchive/archive_process.php')
    .replaceAll('{{courseid}}', '42').replaceAll('{{sesskey}}', 'test-session');
const sections = [{name: 'Section', visible: true, modules: [
    {cmid: 7, name: '<img src=x onerror=alert(1)>', type: 'page', visible: true},
    {cmid: 8, name: 'Hidden book', type: 'book', visible: false},
    {cmid: 9, name: 'Text and media', type: 'label', visible: true},
    {cmid: 10, name: 'File resource', type: 'resource', visible: true},
    {cmid: 11, name: 'Folder resource', type: 'folder', visible: true},
    {cmid: 12, name: 'Glossary', type: 'glossary', visible: true},
    {cmid: 13, name: 'Wiki', type: 'wiki', visible: true},
    {cmid: 14, name: 'Feedback', type: 'feedback', visible: true}
].map(item => ({...item, supported: true, selectable: true, iconurl: `https://moodle.example/theme/image.php/boost/${item.type}/1/icon`,
    modfullname: `Type ${item.type}`}))}];

async function setup(file, response = {sections}, error = null) {
    const dom = new JSDOM(template, {url: 'https://moodle.example/', runScripts: 'outside-only'});
    const alerts = [], errors = [], requests = [];
    let module;
    dom.window.define = (...args) => {
        module = args.at(-1)(
            {call: calls => { requests.push(...calls); return [error ? Promise.reject(error) : Promise.resolve(response)]; }},
            {alert: (...args) => alerts.push(args), exception: error => errors.push(error)},
            {get_strings: async items => items.map(item => item.key)}
        );
    };
    dom.window.eval(fs.readFileSync(path.join(root, file), 'utf8'));
    await module.init({courseid: 42});
    const document = dom.window.document;
    const submit = () => {
        const event = new dom.window.Event('submit', {bubbles: true, cancelable: true});
        document.querySelector('form').dispatchEvent(event);
        return event;
    };
    return {dom, document, alerts, errors, requests, module, submit};
}

for (const file of ['amd/src/selectors.js', 'amd/build/selectors.min.js']) {
    test(`${file}: selection, safe titles and POST contract`, async t => {
        const ctx = await setup(file);
        t.after(() => ctx.dom.window.close());
        const {document, dom} = ctx;
        assert.equal(ctx.requests[0].methodname, 'local_flatarchive_get_tree');
        assert.equal(ctx.requests[0].args.courseid, 42);
        assert.equal(document.querySelectorAll('input[name="cmid[]"]').length, 8);
        const icons = document.querySelectorAll('[data-region="tree"] img');
        assert.equal(icons.length, 8);
        assert.equal(document.querySelector('[onerror]'), null);
        icons.forEach((icon, index) => {
            const item = sections[0].modules[index];
            assert.equal(icon.src, item.iconurl);
            assert.equal(icon.alt, item.modfullname);
            assert.equal(icon.previousElementSibling.value, String(item.cmid));
            assert.equal(icon.nextElementSibling.tagName, 'SPAN');
        });
        assert.match(document.querySelector('[data-region="tree"]').textContent, /<img/);
        assert.equal(document.querySelector('.badge').textContent, 'hidden');
        document.querySelector('[data-action="selectall"]').click();
        assert.equal(document.querySelectorAll('input:checked').length, 8);
        document.querySelector('[data-action="deselectall"]').click();
        assert.equal(document.querySelectorAll('input:checked').length, 0);
        assert.equal(ctx.submit().defaultPrevented, true);
        assert.equal(ctx.alerts.length, 1);
        document.querySelector('input[value="14"]').checked = true;
        const form = document.querySelector('form');
        assert.equal(form.method, 'post');
        assert.equal(new URL(form.action).pathname, '/local/flatarchive/archive_process.php');
        assert.equal(ctx.submit().defaultPrevented, false);
        assert.deepEqual(Array.from(new dom.window.FormData(form).entries()), [
            ['id', '42'], ['sesskey', 'test-session'], ['cmid[]', '14']
        ]);
        assert.equal(ctx.submit().defaultPrevented, true);
        dom.window.dispatchEvent(new dom.window.PageTransitionEvent('pageshow', {persisted: true}));
        assert.equal(document.querySelector('[data-action="start-archive"]').disabled, false);
        await ctx.module.init({courseid: 42});
        assert.equal(ctx.requests.length, 1);
    });
    test(`${file}: unsupported activities remain visible but never enter the POST`, async t => {
        const modules = [
            {...sections[0].modules[0]},
            {cmid: 20, name: 'Assignment', supported: false, selectable: false, visible: true},
            {cmid: 21, name: 'Lesson', supported: true, selectable: false, visible: true},
        ];
        const ctx = await setup(file, {sections: [{name: 'All activities', visible: true, modules}]});
        t.after(() => ctx.dom.window.close());
        const {document, dom} = ctx;
        assert.equal(document.querySelectorAll('input[name="cmid[]"]').length, 3);
        const disabled = document.querySelector('input[value="20"]');
        assert.equal(disabled.disabled, true);
        assert.equal(document.getElementById(disabled.getAttribute('aria-describedby')).textContent, 'unsupportedactivity');
        assert.equal(document.querySelector('input[value="21"]').disabled, true);
        assert.match(document.querySelector('[data-region="tree"]').textContent, /exportnotpermitted/);
        document.querySelector('[data-action="selectall"]').click();
        assert.equal(document.querySelectorAll('input:checked').length, 1);
        // Even a disabled control checked by another script is ignored and not posted.
        disabled.checked = true;
        assert.equal(ctx.submit().defaultPrevented, false);
        assert.deepEqual(Array.from(new dom.window.FormData(document.querySelector('form')).getAll('cmid[]')), ['7']);
    });
    test(`${file}: unsupported-only section is shown and export stays disabled`, async t => {
        const ctx = await setup(file, {sections: [{name: 'Unsupported section', visible: true,
            modules: [{cmid: 20, name: 'Assignment', supported: false, selectable: false, visible: true}]}]});
        t.after(() => ctx.dom.window.close());
        assert.match(ctx.document.querySelector('[data-region="tree"]').textContent, /Unsupported section/);
        assert.equal(ctx.document.querySelector('[data-action="selectall"]').disabled, true);
        assert.equal(ctx.document.querySelector('[data-action="start-archive"]').disabled, true);
        assert.equal(ctx.submit().defaultPrevented, true);
    });
    test(`${file}: empty course cannot submit`, async t => {
        const ctx = await setup(file, {sections: []});
        t.after(() => ctx.dom.window.close());
        assert.equal(ctx.document.querySelector('[data-region="status"]').textContent, 'noresources');
        assert.equal(ctx.document.querySelector('[data-action="start-archive"]').disabled, true);
        assert.equal(ctx.submit().defaultPrevented, true);
    });
    test(`${file}: server errors are visible and block export`, async t => {
        const error = new Error('Access denied');
        const ctx = await setup(file, null, error);
        t.after(() => ctx.dom.window.close());
        assert.deepEqual(ctx.errors, [error]);
        assert.equal(ctx.document.querySelector('[data-region="loading"]').hidden, true);
        assert.equal(ctx.document.querySelector('[data-region="status"]').textContent, 'status_failed');
        assert.equal(ctx.submit().defaultPrevented, true);
    });
}
