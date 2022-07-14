/**
 * Copy assets-src to assets folder.
 */


'use strict';


import fs from 'node:fs';
import path from 'node:path';
// import this app's useful class.
import FS from '../Libraries/FS.mjs';
import TextStyles from '../Libraries/TextStyles.mjs';


export default class CopyAssetsSrc {


    /**
     * Static class constructor.
     */
    static() {
        hasInfo = false;
    }// static


    /**
     * Copy CSS
     * 
     * @async
     * @private This method was called from `run()`.
     * @returns {Promise} Return `Promise` object.
     */
    static async #copyCSS() {
        const assetGlobPatterns = ['assets-src/css/**'];
        const assetsDest = 'assets/css';

        const filesResult = await this.#getFilesResult(assetGlobPatterns);

        if (typeof(filesResult) === 'object' && filesResult.length > 0) {
            // parallel work loop.
            await Promise.all(
                filesResult.map(async (eachFile) => {
                    await this.#doCopy(assetGlobPatterns, eachFile, assetsDest);
                })
            );// end Promise.all
        } else {
            console.log('    ' + TextStyles.txtInfo('Patterns `' + assetGlobPatterns + '`: Result not found.'));
            this.hasInfo = true;
        }

        return Promise.resolve();
    }// copyCSS


    /**
     * Copy images
     * 
     * @async
     * @private This method was called from `run()`.
     * @returns {Promise} Return `Promise` object.
     */
    static async #copyImg() {
        const assetGlobPatterns = ['assets-src/img/**'];
        const assetsDest = 'assets/img';

        const filesResult = await this.#getFilesResult(assetGlobPatterns);

        if (typeof(filesResult) === 'object' && filesResult.length > 0) {
            // parallel work loop.
            await Promise.all(
                filesResult.map(async (eachFile) => {
                    await this.#doCopy(assetGlobPatterns, eachFile, assetsDest);
                })
            );// end Promise.all
        } else {
            console.log('    ' + TextStyles.txtInfo('Patterns `' + assetGlobPatterns + '`: Result not found.'));
            this.hasInfo = true;
        }

        return Promise.resolve();
    }// copyImg


    /**
     * Copy JS
     * 
     * @async
     * @private This method was called from `run()`.
     * @returns {Promise} Return `Promise` object.
     */
    static async #copyJS() {
        const assetGlobPatterns = ['assets-src/js/**'];
        const assetsDest = 'assets/js';

        const filesResult = await this.#getFilesResult(assetGlobPatterns);

        if (typeof(filesResult) === 'object' && filesResult.length > 0) {
            // parallel work loop.
            await Promise.all(
                filesResult.map(async (eachFile) => {
                    await this.#doCopy(assetGlobPatterns, eachFile, assetsDest);
                })
            );// end Promise.all
        } else {
            console.log('    ' + TextStyles.txtInfo('Patterns `' + assetGlobPatterns + '`: Result not found.'));
            this.hasInfo = true;
        }

        return Promise.resolve();
    }// copyJS


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
            console.log('    >> ' + sourcePath);
            console.log('      Copied to -> ' + destPath);
            return Promise.resolve();
        });// end promise;
    }// doCopy


    /**
     * Get file result.
     * 
     * @async
     * @private This method was called from `copyXX()`.
     * @param {string|string[]} patterns Glob patterns.
     * @returns {string[]} Return file result in array.
     */
    static async #getFilesResult(patterns) {
        const filesResult = await FS.glob(
            patterns, {
                absolute: false,
                cwd: MODULE_DIR,
            }
        );

        if (typeof(filesResult) === 'object' && Array.isArray(filesResult)) {
            return filesResult;
        }
        return [];
    }// getFilesResult


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


    /**
     * Initialize the class.
     * 
     * @async
     */
    static run() {
        console.log('  Copy assets-src to assets folder.');

        let tasks = [];
        tasks.push(
            this.#copyCSS()
        );
        tasks.push(
            this.#copyImg()
        );
        tasks.push(
            this.#copyJS()
        );

        return Promise.all(tasks)
        .then(() => {
            if (this.hasInfo) {
                console.log('    ' + TextStyles.txtInfo('There is at least one information, please read the result.'));
            }
            return Promise.resolve();
        })
        .then(() => {
            console.log('  End copy assets-src.');
            return Promise.resolve();
        });// end Promise.
    }// run


}