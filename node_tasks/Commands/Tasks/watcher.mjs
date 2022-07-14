/**
 * Watcher task.
 */


'use strict';


import del from 'del';
import path from 'node:path';
// import this app's useful class.
import FS from "../../Libraries/FS.mjs";
import NtConfig from "../../Libraries/NtConfig.mjs";
import TextStyles from "../../Libraries/TextStyles.mjs";
// import tasks for this command.
import {assetsCopier} from './assetsCopier.mjs';
import {assetsSrcCopier} from './assetsSrcCopier.mjs';


export const watcher = class Watcher {


    /**
     * Class constructor.
     * 
     * @param {Object} argv The CLI arguments.
     */
    constructor(argv = {}) {
        /**
         * @type {Object} The command line argument.
         */
        this.argv = {};
        if (typeof(argv) === 'object') {
            this.argv = {
                ...this.argv,
                ...argv,
            }
        }
    }// constructor


    /**
     * Apply changes to destination.
     * 
     * @link https://www.npmjs.com/package/del The dependent Node package.
     * @async
     * @private This method was called from `watch()`.
     * @param {string} event The watcher events. See https://github.com/paulmillr/chokidar#methods--events
     * @param {string} file The changed file.
     */
    async #applyChanges(event, file) {
        let command;

        if (event.toLowerCase().indexOf('unlink') !== -1) {
            // if matched unlink (file), unlinkDir (folder)
            command = 'delete';
        } else {
            // if matched add, addDir, change
            command = null;
        }

        if (command === 'delete') {
            // if command is delete (file and folder).
            const assetsSrcFullPath = path.resolve(MODULE_DIR, 'assets-src');
            const assetFileRelPath = path.relative(assetsSrcFullPath, file);
            
            const copyAssetsSrcObj = NtConfig.getValue('copyAssetsSrc', {});
            if (typeof(copyAssetsSrcObj) === 'object' && Object.keys(copyAssetsSrcObj).length > 0) {
                // if config has `copyAssetsSrc` property.
                const assetsFullPath = path.resolve(MODULE_DIR, 'assets');
                // delete file/folder on assets folder.
                const deleteResult = await del(assetFileRelPath, {cwd: assetsFullPath, force: true});
                for (const item of deleteResult) {
                    console.log('    - Deleted: ' + item);
                };
            }

            const copyAssetsToPublic = NtConfig.getValue('copyAssetsToPublic', false);
            if (copyAssetsToPublic === true && this.argv.nocppublic !== true) {
                // if config has `copyAssetsToPublic` and marked as `true`.
                // and if CLI argument is not set to "not copy to public folder".
                // delete file/folder on public module assets folder too.
                const rdbPublicModuleAssetsDirRealPath = path.resolve(rdbPublicModuleAssetsDir);
                const deleteResult = await del(assetFileRelPath, {cwd: rdbPublicModuleAssetsDirRealPath, force: true});
                for (const item of deleteResult) {
                    console.log('    - Deleted: ' + item);
                };
            }
        }
        
        if (command !== 'delete') {
            // else, it is copy command.
            await assetsSrcCopier.run(this.argv);
            if (this.argv.nocppublic !== true) {
                // if CLI argument is not set to "not copy to public folder".
                await assetsCopier.copy(this.argv);
            }
        }// endif;

        return Promise.resolve();
    }// applyChanges


    /**
     * Display file changed.
     * 
     * @private This method was called from `watch()`.
     * @param {string} event The watcher events. See https://github.com/paulmillr/chokidar#methods--events
     * @param {string} file The changed file.
     * @param {string} source The source folder full path.
     */
    #displayFileChanged(event, file, source) {
        console.log('  File changed (' + event + '): ' + path.resolve(source, file));
    }// displayFileChanged


    /**
     * Watch selected source and copy/bundle[/and maybe minify] to assets folder.  
     * And then maybe copy to public's assets folder in the end.
     */
    watch() {
        console.log(TextStyles.taskHeader('Watch module\'s asset files changes.'));

        const watcher = FS.watch(
            ['assets-src/**'], 
            {
                cwd: MODULE_DIR,
            }
        );

        watcher.on('all', async (event, file, stats) => {
            await this.#displayFileChanged(event, file, MODULE_DIR);
            await this.#applyChanges(event, file);
            console.log('  Finish task for file changed (' + event + ').');
        });
    }// watch


}