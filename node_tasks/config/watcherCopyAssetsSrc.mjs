/**
 * Copy files, folders from watcher where based on assets-src to assets folder.
 */


'use strict';


import path from 'node:path';
import url from 'node:url';
// import libraries.
const {default: FS} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/RdbDev/Libraries/FS.mjs"));


export default class WatcherCopyAssetsSrc {


    /**
     * @type {object} The CLI arguments.
     */
    argv = {};


    /**
     * @type {object} The pathsJSON that filtered to use only modules that contain config/config.json file.
     */
    pathsJSON = {};


    /**
     * Class constructor.
     * 
     * @param {object} argv The CLI arguments.
     * @param {object} pathsJSON The pathsJSON that filtered to use only modules that contain config/config.json file.
     */
    constructor(argv, pathsJSON) {
        if (typeof(argv) === 'object') {
            this.argv = argv;
        } else {
            this.argv = {};
        }

        if (typeof(pathsJSON) === 'object') {
            this.pathsJSON = pathsJSON;
        } else {
            this.pathsJSON = {};
        }
    }// constructor


    /**
     * Run copy assets-src tasks.
     * 
     * @param {object} options The options. These options also use in config tasks runner.
     * @param {string} options.file The watched file result.
     * @param {string} options.fileInAssetsPath The watched file but replaced to assets folder path.
     * @param {string} options.module A module name based on paths.json.
     * @param {string} options.moduleWorkingDir Current module working folder (source folder).
     * @returns {Promise} Return Promise object.
     */
    static async run(options = {}) {
        if (typeof(options?.file) !== 'string') {
            throw new Error('The argument options.file is required and must be string.');
        }
        if (typeof(options?.fileInAssetsPath) !== 'string') {
            throw new Error('The argument options.fileInAssetsPath is required and must be string.');
        }
        if (typeof(options?.moduleWorkingDir) !== 'string') {
            throw new Error('The argument options.moduleWorkingDir is required and must be string.');
        }

        const thisClass = new this(options?.argv, options?.pathsJSON);
        const sourcePath = path.resolve(options.moduleWorkingDir, options.file);
        const destPath = path.resolve(options.moduleWorkingDir, options.fileInAssetsPath);

        if (sourcePath === destPath) {
            // if file in source and destination is same.
            // do nothing here.
            return Promise.resolve();
        }

        FS.cpSync(sourcePath, destPath);

        console.log('      >> ' + sourcePath);
        console.log('        Copied to -> ' + destPath);
        return Promise.resolve();
    }// run


}