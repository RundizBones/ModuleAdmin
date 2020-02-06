/**
 * Concat/bundle Moment.js.
 * 
 * Also minify them.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
const print = require('gulp-print').default;


function bundleMoment(cb) {
    const concat = require('gulp-concat');
    const mergeStream =   require('merge-stream');
    const header = require('gulp-header');

    return mergeStream(
        src([
            'node_modules/moment/min/moment-with-locales.min.js',
            'node_modules/moment-timezone/builds/moment-timezone-with-data.min.js',
        ])
            .pipe(print())
            .pipe(concat('moment-bundled.js'))
            .pipe(dest('assets/vendor/moment/'))
            .pipe(print())
    );
}// bundleMoment


function minifyMoment(cb) {
    const rename = require("gulp-rename");
    const uglify = require('gulp-uglify-es').default;

    return src('assets/vendor/moment/moment-bundled.js')
        .pipe(print())
        .pipe(rename('moment-bundled.min.js'))
        .pipe(uglify({
            output: {
                comments: false
            }
        }))
        .pipe(dest('assets/vendor/moment/'))
        .pipe(print());
}// minifyMoment


exports.bundleAndMinify = series(
    bundleMoment,
    minifyMoment
);