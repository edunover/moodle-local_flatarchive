const fs = require('node:fs/promises');
const path = require('node:path');
const {minify} = require('terser');
(async () => {
    const root = path.resolve(__dirname, '..');
    for (const name of ['selectors', 'appearance']) {
        const source = await fs.readFile(path.join(root, `amd/src/${name}.js`), 'utf8');
        const named = source.replace('define([', `define('local_flatarchive/${name}', [`);
        const result = await minify({[`${name}.js`]: named}, {
            sourceMap: {filename: `${name}.min.js`, url: `${name}.min.js.map`},
            format: {comments: /@license/}
        });
        await fs.mkdir(path.join(root, 'amd/build'), {recursive: true});
        await fs.writeFile(path.join(root, `amd/build/${name}.min.js`), result.code + '\n');
        await fs.writeFile(path.join(root, `amd/build/${name}.min.js.map`), result.map + '\n');
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
