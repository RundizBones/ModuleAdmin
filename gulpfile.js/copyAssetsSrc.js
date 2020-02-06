/**
 * Copy assets-src to assets folder.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
const cache = require('gulp-cached');


/**
 * Copy assets-src/css to assets/css folder.
 */
function copyAssetsSrcCss(cb) {
    console.log('Copying assets-src/css to assets/css');
    return src('assets-src/css/**')
        .pipe(cache('copyAssetsSrcCss'))
        .pipe(dest('assets/css/'));
}// copyAssetsSrcCss


/**
 * Copy assets-src/img to assets/img folder.
 */
function copyAssetsSrcImg(cb) {
    console.log('Copying assets-src/img to assets/img');
    return src('assets-src/img/**')
        .pipe(cache('copyAssetsSrcImg'))
        .pipe(dest('assets/img/'));
}// copyAssetsSrcImg


/**
 * Copy assets-src/js to assets/js folder.
 */
function copyAssetsSrcJs(cb) {
    console.log('Copying assets-src/js to assets/js');
    return src('assets-src/js/**')
        .pipe(cache('copyAssetsSrcJs'))
        .pipe(dest('assets/js/'));
}// copyAssetsSrcJs


exports.copyAssetsSrcCssJs = parallel(
    copyAssetsSrcCss,
    copyAssetsSrcJs,
    copyAssetsSrcImg
);