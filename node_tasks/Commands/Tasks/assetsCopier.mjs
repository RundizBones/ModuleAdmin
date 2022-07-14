/**
 * Assets copier.
 * 
 * Copy the whole assets folder to public folder.
 * 
 * config.json example:
 ```
    "copyAssetsToPublic": true
 ```
 */


'use strict';


import path from 'node:path';
// import this app's useful class.
import FS from '../../Libraries/FS.mjs';
import NtConfig from '../../Libraries/NtConfig.mjs';
import TextStyles from '../../Libraries/TextStyles.mjs';


export const assetsCopier = class AssetsCopier {


    /**
     * @type {number} For count number of copied.
     */
    static copyCount = 0;


    /**
     * Copy the whole assets folder to "public/Modules/<this module name>/assets" folder.
     * @param {Object} argv The CLI arguments.
     */
    static async copy(argv) {
        if (argv.nocppublic === true) {
            // if CLI argument is set to not copy to public folder.
            // don't do anything here.
            return Promise.resolve();
        }

        const assetGlobPatterns = ['assets/**'];
        const moduleAssetsDir = path.resolve(MODULE_DIR, 'assets');
        const rdbPublicModuleAssetsDirRealPath = path.resolve(rdbPublicModuleAssetsDir);
        if (rdbPublicModuleAssetsDirRealPath === moduleAssetsDir) {
            // if it is the same folder.
            // don't do anything here.
            return Promise.resolve();
        }

        const configCopyAssetsToPublic = NtConfig.getValue('copyAssetsToPublic', false);
        if (configCopyAssetsToPublic === false) {
            // if config is not set or was set to do not copy.
            // don't do anything here.
            return Promise.resolve();
        }

        console.log(TextStyles.taskHeader('Copy assets tasks.'));

        this.copyCount = 0;
        const filesResult = await FS.glob(assetGlobPatterns, {absolute: false, cwd: MODULE_DIR});

        if (typeof(filesResult) === 'object' && filesResult.length > 0) {
            // parallel work loop.
            await Promise.all(
                filesResult.map(async (eachFile) => {
                    await this.#doCopy(assetGlobPatterns, eachFile, rdbPublicModuleAssetsDirRealPath);
                })
            );// end Promise.all
            console.log('  ' + TextStyles.txtSuccess(this.copyCount + ' Files & folders were copied.'));
        } else {
            console.log('  ' + TextStyles.txtInfo('Patterns `' + assetGlobPatterns + '`: Result not found.'));
        }

        console.log('End copy assets tasks.');
        return Promise.resolve();
    }// copy


    /**
     * Do copy file and folder to destination.
     * 
     * @async
     * @private This method was called from `copyXX()`.
     * @param {string|string[]} globPatterns Glob patterns.
     * @param {string} eachFile Each file name (and folder) from the search (glob) result.
     * @param {string} assetsDest Destination folder.
     * @returns {Promise} Return `Promise` object.
     */
    static async #doCopy(globPatterns, eachFile, assetsDest) {
        const fileResultParent = await this.#getResultParent(globPatterns);
        const relativeName = path.relative(fileResultParent, eachFile);
        const sourcePath = path.resolve(MODULE_DIR, eachFile);
        const destPath = path.resolve(MODULE_DIR, assetsDest, relativeName);

        if (FS.isExactSame(sourcePath, destPath)) {
            // if source and destination are the exactly same.
            // skip it.
            return Promise.resolve();
        }

        return FS.copyFileDir(sourcePath, destPath)
        .then(() => {
            console.log('  >> ' + sourcePath);
            console.log('    Copied to -> ' + destPath);
            this.copyCount++;
            return Promise.resolve();
        });// end promise;
    }// doCopy


    /**
     * Get result's parent for use in replace and find only relative result from the patterns.
     * 
     * Example: patterns are 'assets-src/css/**'  
     * The result of files can be 'assets-src/css/folder/style.css'  
     * The result that will be return is 'folder'.
     * 
     * @async
     * @private This method was called from `doCopy()`.
     * @param {string|string[]} patterns Glob patterns.
     * @returns {string} Return retrieved parent of this pattern.
     */
    static async #getResultParent(patterns) {
        const filesResult1lv = await FS.glob(
            patterns,
            {
                absolute: false,
                cwd: MODULE_DIR,
                deep: 1,
            }
        );

        let fileResultParent = '';
        for (let eachFile in filesResult1lv) {
            fileResultParent = path.dirname(filesResult1lv[eachFile]);
            break;
        }

        return fileResultParent;
    }// getResultParent


}