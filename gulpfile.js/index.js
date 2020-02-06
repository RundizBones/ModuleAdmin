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
const pack = require('./pack');
const versionWriter = require('./versionWriter');

global.rdbPublicModuleAssetsDir = '../../public/Modules/RdbAdmin/assets';


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


exports.default = series(
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
    watch('assets-src/**', {events: 'all'}, series(watchFileChanged, copyAssetsSrc.copyAssetsSrcCssJs, copyAssets.copyAssets))
};


exports.pack = series(
    pack.pack
);