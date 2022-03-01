/**
 * Concat/bundle Datatables.
 * 
 * Also minify them.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
const print = require('gulp-print').default;
const sourcemaps = require('gulp-sourcemaps');
const concat = require('gulp-concat');


/**
 * JS files to concat.
 */
const jsToConcat = [
    'node_modules/datatables.net/js/jquery.dataTables.js',
    'node_modules/datatables.net-fixedheader/js/dataTables.fixedHeader.js',
    'node_modules/datatables.net-responsive/js/dataTables.responsive.js',
];
/**
 * CSS files to concat
 */
const cssToConcat = [
    'node_modules/datatables.net-dt/css/jquery.dataTables.css',
    'node_modules/datatables.net-fixedheader-dt/css/fixedHeader.dataTables.css',
    'node_modules/datatables.net-responsive-dt/css/responsive.dataTables.css',
];


/**
 * Concat/bundle DataTables CSS & JS.
 * 
 * This is not yet minify.
 */
function bundleDataTables(cb) {
    const mergeStream =   require('merge-stream');
    const header = require('gulp-header');

    return mergeStream(
        src(jsToConcat, {
            base: 'node_modules/'
        })
            .pipe(sourcemaps.init({loadMaps: true}))
            .pipe(concat('datatables-bundled.js'))
            .pipe(sourcemaps.write('.'))
            .pipe(dest('assets/vendor/datatables.net/js/'))
            .pipe(print()),

        src(cssToConcat)
            .pipe(print())
            .pipe(concat('datatables-bundled.css'))
            .pipe(dest('assets/vendor/datatables.net/css/'))
            .pipe(print())
    );
}// bundleDataTables


/**
 * Minify DataTables JS files.
 */
function minifyDataTablesJs(cb) {
    const rename = require("gulp-rename");
    const uglify = require('gulp-uglify-es').default;

    return src(jsToConcat, {
            base: 'node_modules/'
        })
            .pipe(sourcemaps.init({loadMaps: true}))
            .pipe(concat('datatables-bundled.js'))
            .pipe(uglify({
                output: {
                    comments: false
                }
            }))
            .pipe(rename(function (path) {
                path.basename += ".min";
            }))
            .pipe(sourcemaps.write('.'))
            .pipe(dest('assets/vendor/datatables.net/js/'))
            .pipe(print());
}// minifyDataTablesJs


/**
 * Minify DataTables CSS files.
 */
function minifyDataTablesCss(cb) {
    const rename = require("gulp-rename");
    const cleanCSS = require('gulp-clean-css');

    return src('assets/vendor/datatables.net/css/datatables-bundled.css')
        .pipe(print())
        .pipe(cleanCSS({
            level: {
                1: {
                    specialComments: 0
                }
            }
        }))
        .pipe(rename('datatables-bundled.min.css'))
        .pipe(dest('assets/vendor/datatables.net/css/'))
        .pipe(print());
}// minifyDataTablesCss


exports.bundleAndMinify = series(
    bundleDataTables,
    minifyDataTablesJs,
    minifyDataTablesCss
);