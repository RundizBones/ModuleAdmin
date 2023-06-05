/**
 * Concat/bundle Rundiz Template for Admin assets.
 * 
 * Also minify them.
 */


'use strict';


import url from 'node:url';
// import libraries.
const {default: Concat} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/RdbDev/Libraries/Concat.mjs"));
const {default: MinCSS} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/RdbDev/Libraries/MinCSS.mjs"));
const {default: MinJS} = await import(url.pathToFileURL(RDBDEV_APPDIR + "/app/RdbDev/Libraries/MinJS.mjs"));


const cssFiles = [
    // use sanitize.css from its package directly. no need for page.css because it will be mess with design.
    'node_modules/sanitize.css/sanitize.css',
    'node_modules/sanitize.css/typography.css',
    'node_modules/sanitize.css/forms.css',
    // use fontawesome from its package directly.
    'node_modules/@fortawesome/fontawesome-free/css/all.min.css',
    // use smartmenus from its package directly.
    'node_modules/smartmenus/dist/css/sm-core-css.css',
    // RDTA assets.
    'node_modules/rundiz-template-for-admin/assets/css/smartmenus/sm-rdta/sm-rdta.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/typo-and-form/typo-and-form.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/rundiz-template-admin.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/columns/columns-flex.css',
    // RDTA additional components. -------------
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-accordion.min.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-alertdialog.min.css',
    // keep rdta-datatables-js.min.css away until updated in RdbAdmin module to supported it and not conflict with one from module itself.
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-dialog.min.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-embeds.min.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-tabs.min.css',
];

const jsFiles = [
    // use jquery from its package directly.
    'node_modules/jquery/dist/jquery.min.js', 
    // use smartmenus from its package directly.
    'node_modules/smartmenus/dist/jquery.smartmenus.min.js',
    // use popper from its package directly.
    'node_modules/@popperjs/core/dist/umd/popper.min.js',
    // use sticky-sidebar from its package directly.
    'node_modules/sticky-sidebar/dist/jquery.sticky-sidebar.min.js',
    // use resizesensor from its package directly.
    'node_modules/css-element-queries/src/ResizeSensor.js',
    // RDTA assets.
    'node_modules/rundiz-template-for-admin/assets/js/rdta/rundiz-template-admin.js',
    // RDTA additional components. -------------
    'node_modules/rundiz-template-for-admin/assets/js/rdta/components/rdta-accordion.min.js',
    'node_modules/rundiz-template-for-admin/assets/js/rdta/components/rdta-alertdialog.min.js',
    'node_modules/rundiz-template-for-admin/assets/js/rdta/components/rdta-dialog.min.js',
    'node_modules/rundiz-template-for-admin/assets/js/rdta/components/rdta-tabs.min.js',
    // tooltips (tippy)
    'node_modules/tippy.js/dist/tippy-bundle.umd.min.js',
    'node_modules/rundiz-template-for-admin/assets/js/rdta/components/rdta-tooltips.min.js',
];

const destCSSFolder = 'assets/css/rdta';
const destJSFolder = 'assets/js/rdta';

const headerString = '/*! Rundiz Template for Admin*/\n\n';


export default class BundleRdta {


    static get cssFiles() {
        return cssFiles;
    }// cssFiles


    static get destCSSFolder() {
        return destCSSFolder;
    }// destCSSFolder


    static get destJSFolder() {
        return destJSFolder;
    }// destJSFolder

    
    static get headerString() {
        return headerString;
    }// headerString
    

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
        await concat.concat('rdta-bundled.css');
        await concat.cleanCSS({sourceMapInlineSources: true});
        await concat.header(this.headerString);
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
        await concat.concat('rdta-bundled.js');
        await concat.cleanJS();
        await concat.header(this.headerString);
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
                sourceMapInlineSources: true,
            }
        });
        await minCSS.minify('rdta-bundled.min.css');
        await minCSS.header(this.headerString);
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
        await minJS.minify('rdta-bundled.min.js');
        await minJS.header(this.headerString);
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
     * Run bundle RDTA.
     * 
     * @async
     */
    static run() {
        console.log('  Bundle RDTA.');

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
            console.log('  End bundle RDTA.');
            return Promise.resolve();
        });// end Promise.
    }// run


}