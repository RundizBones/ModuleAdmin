/**
 * Copy module assets to public/Modules/[module_name]/assets folder.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
const cache = require('gulp-cached');
const path = require('path');


function copyAssetsToPublic(cb) {
    console.log('Copying assets to ' + path.resolve(rdbPublicModuleAssetsDir));
    return src('assets/**')
        .pipe(cache('copyAssetsToPublic'))
        .pipe(dest(rdbPublicModuleAssetsDir + '/'));
}// copyAssetsToPublic


exports.copyAssets = parallel(
    copyAssetsToPublic
);