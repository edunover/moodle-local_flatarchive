const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
for (const file of ['amd/src/appearance.js', 'amd/build/appearance.min.js']) {
    test(`${file}: preserve edits until explicit preset change`, t => {
        const dom = new JSDOM('<select id="id_s_local_flatarchive_stylepreset"><option value="none">None</option><option value="modern">Modern</option></select><textarea id="id_s_local_flatarchive_customcss">Saved custom CSS</textarea>', {runScripts: 'outside-only'});
        t.after(() => dom.window.close());
        let module;
        dom.window.define = (...args) => { module = args.at(-1)(); };
        dom.window.eval(fs.readFileSync(path.resolve(__dirname, '..', file), 'utf8'));
        const presets = {none: '', modern: 'body {color: green;}'};
        module.init(presets);
        module.init(presets);
        const select = dom.window.document.querySelector('select');
        const field = dom.window.document.querySelector('textarea');
        assert.equal(field.value, 'Saved custom CSS');
        let changes = 0;
        field.addEventListener('input', () => changes++);
        select.value = 'modern';
        select.dispatchEvent(new dom.window.Event('change'));
        assert.equal(field.value, presets.modern);
        assert.equal(changes, 1);
        field.value += '\n/* Institution changes */';
        module.init(presets);
        assert.match(field.value, /Institution changes/);
        select.value = 'none';
        select.dispatchEvent(new dom.window.Event('change'));
        assert.equal(field.value, '');
    });
}
