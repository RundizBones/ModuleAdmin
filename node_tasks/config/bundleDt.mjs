/**
 * Concat/bundle Datatables.js
 * 
 * Also minify them.
 */


'use strict';


import url from 'node:url';
// import libraries.
const {default: Concat} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/Libraries/Concat.mjs"));
const {default: MinCSS} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/Libraries/MinCSS.mjs"));
const {default: MinJS} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/Libraries/MinJS.mjs"));


const cssFiles = [
    'node_modules/datatables.net-dt/css/jquery.dataTables.css',
];
const jsFiles = [
    './node_modules/datatables.net/js/jquery.dataTables.js',
    './node_modules/datatables.net-fixedheader/js/dataTables.fixedHeader.js',
    './node_modules/datatables.net-responsive/js/dataTables.responsive.js',
];

const destJSFolder = 'assets/vendor/datatables.net/js';
const destCSSFolder = 'assets/vendor/datatables.net/css';


export default class BundleDt {


    static get cssFiles() {
        return cssFiles;
    }// cssFiles


    static get destCSSFolder() {
        return destCSSFolder;
    }// destCSSFolder


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
     * Concatenating CSS.
     */
    static async #concatCSS() {
        const concat = new Concat({
            sourceFiles: this.cssFiles,
            options: {
                sourceMap: true,
            }
        });
        await concat.concat('datatables-bundled.css');
        concat.cleanCSS();
        return concat.writeFile(this.destCSSFolder)
        .then((result) => {
            console.log('    Concatenated file: ' + result.file);
            if (result.sourceMap) {
                console.log('    Concatenated source map: ' + result.sourceMap);
            }
            return Promise.resolve();
        });
    }// concatCSS


    /**
     * Concatenating JS.
     */
    static async #concatJS() {
        const concat = new Concat({
            sourceFiles: this.jsFiles,
            options: {
                sourceMap: true,
            }
        });
        await concat.concat('datatables-bundled.js');
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
     * Minify CSS.
     */
    static async #minifyCSS() {
        const minCSS = new MinCSS({
            sourceFiles: this.cssFiles,
            options: {
                sourceMap: true,
            }
        });
        await minCSS.minify('datatables-bundled.min.css');
        return minCSS.writeFile(this.destCSSFolder)
        .then((result) => {
            console.log('    Minified file: ' + result.file);
            if (result.sourceMap) {
                console.log('    Minified source map: ' + result.sourceMap);
            }
            return Promise.resolve();
        });
    }// minifyCSS


    /**
     * Minify JS.
     */
    static async #minifyJS() {
        const minJS = new MinJS({
            sourceFiles: this.jsFiles,
            options: {
                sourceMap: true,
            }
        });
        await minJS.minify('datatables-bundled.min.js');
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
        console.log('  Bundle datatables');

        let tasks = [];
        tasks.push(
            this.#concatCSS()
        );
        tasks.push(
            this.#concatJS()
        );
        tasks.push(
            this.#minifyCSS()
        );
        tasks.push(
            this.#minifyJS()
        );

        return Promise.all(tasks)
        .then(() => {
            console.log('  End bundle datatables.');
            return Promise.resolve();
        });// end Promise.
    }// run


}