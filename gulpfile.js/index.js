/**
 * Main Gulp file.
 */


'use strict';


const {series, parallel, src, dest, watch} = require('gulp');
const fs = require('fs');
const bundleRdta = require('./bundleRdta');
const bundleDataTables = require('./bundleDataTables');
const bundleMoment = require('./bundleMoment');
const copyNodeModules = require('./copyNodeModules');
const copyAssetsSrc = require('./copyAssetsSrc');
const copyAssets = require('./copyAssets');
const versionWriter = require('./versionWriter');

global.moduleAssetsDir = 'Modules/RdbAdmin/assets';
global.rdbPublicModuleAssetsDir = '../../public/' + moduleAssetsDir;


/**
 * Delete folders and files in it.
 * 
 * This will also call to `prepareDirs()` function to create folders.
 */
async function clean(cb) {
    const del = require('del');
    await del(['assets']);
    await del([rdbPublicModuleAssetsDir], {force: true});
    await Promise.all([prepareDirs(cb)]);
}// clean


/**
 * Get `PUBLIC_PATH` php constant from command line and re-assign to global variable.
 * 
 * @since 1.1.7
 */
function getPublicPath(cb) {
    const argv = require('yargs').argv;
    const exec = require('node:child_process').exec;
    const path = require('path');

    if (typeof(argv.publicPath) === 'string' && argv.publicPath !== '') {
        let publicPath = argv.publicPath;
        publicPath = path.resolve(publicPath);// trim trailing slash.
        publicPath = publicPath.replace(/\\/g, '/');// normalize path for glob. replace \ to /
        publicPath = publicPath.replace(/\"|\'$/, '');// trim quote(s) at end.
        global.rdbPublicModuleAssetsDir = publicPath + '/' + moduleAssetsDir;
        console.log('re-assigned global.rdbPublicModuleAssetsDir from `--publicPath` argument: ', rdbPublicModuleAssetsDir);
        cb();
    } else {
        exec('php ../../rdb system:constants --name="PUBLIC_PATH"', (err, stdout, stderr) => {
            // the regular expression pattern of php constant has got from https://www.php.net/manual/en/language.constants.php
            const regex = /^([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)(\s+)[=](\s+)(.+)$/im;
            let m;

            if ((m = regex.exec(stdout)) !== null) {
                // The result can be accessed through the `m`-variable.
                if (typeof(m[4]) === 'string') {
                    global.rdbPublicModuleAssetsDir = m[4] + '/' + moduleAssetsDir;
                    console.log('re-assigned global.rdbPublicModuleAssetsDir: ', rdbPublicModuleAssetsDir);
                }
            }
            cb(err);
        });
    }
}// getPublicPath


/**
 * Create folders ready for copy and make bundle files.
 * 
 * @link https://stackoverflow.com/a/49551263/128761 Original source code.
 */
function prepareDirs(cb) {
    const folders = [
        'assets',
        'assets/css',
        'assets/js',
    ];

    folders.forEach(dir => {
        if(!fs.existsSync(dir)) {
            fs.mkdirSync(dir);  
        }   
    });

    cb();
}// prepareDirs


/**
 * Just echo out that file has been changed.
 * 
 * Can't get the file name right now.
 */
function watchFileChanged(cb) {
    console.log('File has been changed.');
    cb();
}// watchFileChanged


// exports. =============================================================
exports.default = series(
    getPublicPath,
    clean,
    parallel(
        copyNodeModules.copyNodeModules
    ),
    bundleMoment.bundleAndMinify,
    bundleDataTables.bundleAndMinify,
    bundleRdta.bundleAndMinify,
    copyAssetsSrc.copyAssetsSrcCssJs,
    copyAssets.copyAssets
);


exports.writeVersions = series(
    versionWriter.writeVersions
);


exports.watch = function() {
    watch(
        'assets-src/**', {events: 'all'}, 
        series(
            getPublicPath,
            watchFileChanged, 
            copyAssetsSrc.copyAssetsSrcCssJs, 
            copyAssets.copyAssets
        )
    )
};