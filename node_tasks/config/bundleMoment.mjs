/**
 * Concat/bundle Moment.js
 * 
 * Also minify them.
 */


'use strict';


// import based class.
import Concat from "../Libraries/Concat.mjs";
import MinJS from '../Libraries/MinJS.mjs';


const jsFiles = [
    'node_modules/moment/min/moment-with-locales.min.js',
    'node_modules/moment-timezone/builds/moment-timezone-with-data.min.js',
];

const destJSFolder = 'assets/vendor/moment';


export default class BundleMoment {


    static get destJSFolder() {
        return destJSFolder;
    }// destJSFolder
    

    /**
     * Get JS files.
     */
    static get jsFiles() {
        return jsFiles;
    }// jsFiles


    /**
     * Concatenating JS.
     */
    static async #concatJS() {
        const concat = new Concat({
            sourceFiles: this.jsFiles,
        });
        await concat.concat('moment-bundled.js');
        return concat.writeFile(this.destJSFolder)
        .then((result) => {
            console.log('    Concatenated file: ' + result.file);
            if (result.sourceMap) {
                console.log('    Concatenated source map: ' + result.sourceMap);
            }
            return Promise.resolve();
        });
    }// concatJS


    /**
     * Minify JS.
     */
    static async #minifyJS() {
        const minJS = new MinJS({
            sourceFiles: this.jsFiles,
        });
        await minJS.minify('moment-bundled.min.js');
        return minJS.writeFile(this.destJSFolder)
        .then((result) => {
            console.log('    Minified file: ' + result.file);
            if (result.sourceMap) {
                console.log('    Minified source map: ' + result.sourceMap);
            }
            return Promise.resolve();
        });
    }// miniJS


    /**
     * Run bundle & minify assets.
     * 
     * @returns {Promise} Return `Promise` object.
     */
    static run() {
        console.log('  Bundle moment.js');

        let tasks = [];
        tasks.push(
            this.#concatJS()
        );
        tasks.push(
            this.#minifyJS()
        );

        return Promise.all(tasks)
        .then(() => {
            console.log('  End bundle moment.js');
            return Promise.resolve();
        });// end Promise.
    }// run


}