/**
 * Deleter task.
 * 
 * config.json example:
 ```
    "clean": {
        "targets": [
            {
                "patterns": "assets",
                "options": {
                    "dryRun": true
                }
            }
        ],
        "publicModuleAssets": true
    }
```
 */


'use strict';


import del from 'del';
// import this app's useful class.
import NtConfig from '../../../Libraries/NtConfig.mjs';
import TextStyles from '../../../Libraries/TextStyles.mjs';


export const deleter = class Deleter {


    /**
     * Delete selected folders in the config.json `clean` property.
     * 
     * @link https://www.npmjs.com/package/del The dependent Node package.
     * @async
     * @param {Object} argv The CLI arguments.
     */
    static async clean(argv) {
        console.log(TextStyles.taskHeader('Delete selected folders.'));

        const cleanObj = NtConfig.getValue('clean', {});

        if (cleanObj?.targets && typeof(cleanObj.targets) === 'object') {
            for (const target of cleanObj.targets) {
                console.log('  Delete patterns: ' + target.patterns);
                let defaultOptions = {
                    cwd: MODULE_DIR,
                }
                let options = (target.options ?? {});
                options = {
                    ...defaultOptions,
                    ... options
                }
                console.log('    With options: ', options);

                const deleteResult = await del(target.patterns, options);
                deleteResult.forEach((item) => {
                    console.log('    - Deleted: ' + item.replaceAll(/\\/g, '/'));
                });// end forEach;
                if (deleteResult.length <= 0) {
                    console.log('    Target is not exists, skipping.');
                }
            };// end for;
        }// endif; cleanObj.targets

        if (
            cleanObj?.publicModuleAssets === true && 
            argv.nocppublic !== true
        ) {
            console.log('  Delete assets folder or public path.');
            await del(
                rdbPublicModuleAssetsDir, 
                {force: true}
            );
            console.log('    - Deleted: ' + rdbPublicModuleAssetsDir);
        }
        console.log('End delete selected folders.');
    }// clean


}