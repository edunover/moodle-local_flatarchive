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
    {cmid: 8, name: 'Hidden book', type: 'book', visible: false}
]}];

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
        assert.equal(document.querySelectorAll('input[name="cmid[]"]').length, 2);
        assert.equal(document.querySelector('[data-region="tree"] img'), null);
        assert.match(document.querySelector('[data-region="tree"]').textContent, /<img/);
        assert.equal(document.querySelector('.badge').textContent, 'hidden');
        document.querySelector('[data-action="selectall"]').click();
        assert.equal(document.querySelectorAll('input:checked').length, 2);
        document.querySelector('[data-action="deselectall"]').click();
        assert.equal(document.querySelectorAll('input:checked').length, 0);
        assert.equal(ctx.submit().defaultPrevented, true);
        assert.equal(ctx.alerts.length, 1);
        document.querySelector('input[value="8"]').checked = true;
        const form = document.querySelector('form');
        assert.equal(form.method, 'post');
        assert.equal(new URL(form.action).pathname, '/local/flatarchive/archive_process.php');
        assert.equal(ctx.submit().defaultPrevented, false);
        assert.deepEqual(Array.from(new dom.window.FormData(form).entries()), [
            ['id', '42'], ['sesskey', 'test-session'], ['cmid[]', '8']
        ]);
        assert.equal(ctx.submit().defaultPrevented, true);
        dom.window.dispatchEvent(new dom.window.PageTransitionEvent('pageshow', {persisted: true}));
        assert.equal(document.querySelector('[data-action="start-archive"]').disabled, false);
        await ctx.module.init({courseid: 42});
        assert.equal(ctx.requests.length, 1);
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
