/**
 * Asset source copier.
 * 
 * Copy assets-src to assets folder (and maybe bundle, compile, minify).
 * 
 * config.json example:
 ```
    "copyAssetsSrc": [
        "config/copyAssetsSrc.mjs"
    ],
```
 */


'use strict';


import fs from 'node:fs';
import path from 'node:path';
import url from 'node:url';
// import this app's useful class.
import NtConfig from "../../Libraries/NtConfig.mjs";
import TextStyles from '../../Libraries/TextStyles.mjs';


export const assetsSrcCopier = class AssetsSrcCopier {


    /**
     * Run copy asset source files that are specified in config.
     * 
     * @link https://stackoverflow.com/questions/37576685/using-async-await-with-a-foreach-loop Original source of sequence promise, await/async.
     * @param {Object} argv The CLI arguments.
     * @returns {Promise} Return `Promise` object.
     */
     static async run(argv) {
        const copyAssetsSrcObj = NtConfig.getValue('copyAssetsSrc', {});

        if (!copyAssetsSrcObj || typeof(copyAssetsSrcObj) !== 'object' || Object.keys(copyAssetsSrcObj).length <= 0) {
            // if config has no `copyAssetsSrc` property.
            return Promise.resolve();
        }

        console.log(TextStyles.taskHeader('Copy assets-src tasks.'));

        // load files and run in sequence.
        for (const configJS of copyAssetsSrcObj) {
            const fullPathConfigJS = path.resolve(NODETASKS_DIR, configJS);
            if (fs.existsSync(fullPathConfigJS)) {
                const {default: copyClass} = await import(url.pathToFileURL(fullPathConfigJS));
                await copyClass.run();
            }
        }// endfor;

        console.log('End copy assets-src tasks.');
        return Promise.resolve();
    }// run


}