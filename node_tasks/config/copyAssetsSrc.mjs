/**
 * Copy assets-src to assets folder.
 */


'use strict';


import path from 'node:path';
import url from 'node:url';
// import libraries.
const {default: FS} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/Libraries/FS.js"));
const {default: Paths} = await import(url.pathToFileURL(RDBDEV_APPDIR + '/app/Libraries/Paths.js'));
const {default: TextStyles} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/Libraries/TextStyles.mjs"));


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
        const sourcePath = path.resolve(MSW_DIR, eachFile);
        const destPath = path.resolve(MSW_DIR,
            Paths.replaceDestinationFolder(
                eachFile, 
                assetsDest, 
                globPatterns
            )
        );

        if (FS.isExactSame(sourcePath, destPath)) {
            // if source and destination are the exactly same.
            // skip it.
            return Promise.resolve();
        }

        FS.cpSync(sourcePath, destPath);

        console.log('    >> ' + sourcePath);
        console.log('      Copied to -> ' + destPath);
        return Promise.resolve();
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
                cwd: MSW_DIR,
            }
        );

        if (typeof(filesResult) === 'object' && Array.isArray(filesResult)) {
            return filesResult;
        }
        return [];
    }// getFilesResult


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