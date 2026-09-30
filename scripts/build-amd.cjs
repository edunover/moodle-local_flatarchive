const fs = require('node:fs/promises');
const path = require('node:path');
const {minify} = require('terser');
(async () => {
    const root = path.resolve(__dirname, '..');
    const source = await fs.readFile(path.join(root, 'amd/src/selectors.js'), 'utf8');
    const named = source.replace('define([', "define('local_flatarchive/selectors', [");
    const result = await minify({'selectors.js': named}, {
        sourceMap: {filename: 'selectors.min.js', url: 'selectors.min.js.map'},
        format: {comments: /@license/}
    });
    await fs.mkdir(path.join(root, 'amd/build'), {recursive: true});
    await fs.writeFile(path.join(root, 'amd/build/selectors.min.js'), result.code + '\n');
    await fs.writeFile(path.join(root, 'amd/build/selectors.min.js.map'), result.map + '\n');
})().catch(error => { console.error(error); process.exitCode = 1; });
