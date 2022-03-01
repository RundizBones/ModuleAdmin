/**
 * Concat/bundle Rundiz Template for Admin files.
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
/**
 * CSS files to concat
 */
const cssToConcat = [
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
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-dialog.min.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-embeds.min.css',
    'node_modules/rundiz-template-for-admin/assets/css/rdta/components/rdta-tabs.min.css',
];


/**
 * Concat/bundle RDTA CSS & JS.
 * 
 * This is not yet minify.
 */
function bundleRDTA(cb) {
    const cleanCSS = require('gulp-clean-css');
    const mergeStream =   require('merge-stream');
    const header = require('gulp-header');

    var comment = '/*! Rundiz Template for Admin*/\n\n';

    return mergeStream(
        src(jsToConcat, {
            base: 'node_modules/'
        })
            .pipe(header(comment))
            .pipe(sourcemaps.init({loadMaps: true}))
            .pipe(concat('rdta-bundled.js'))
            .pipe(sourcemaps.write('.'))
            .pipe(dest('assets/js/rdta/'))
            .pipe(print()),

        src(cssToConcat)
            .pipe(print())
            .pipe(header(comment))
            .pipe(sourcemaps.init({loadMaps: true}))
            .pipe(concat('rdta-bundled.css'))
            .pipe(cleanCSS({
                format: 'beautify'
            }))
            .pipe(sourcemaps.write('.'))
            .pipe(dest('assets/css/rdta/'))
            .pipe(print())
    );
}// bundleRDTA


/**
 * Minify RDTA CSS files.
 */
function minifyRDTACss(cb) {
    const rename = require("gulp-rename");
    const cleanCSS = require('gulp-clean-css');
    const header = require('gulp-header');

    var comment = '/*! Rundiz Template for Admin*/\n\n';

    return src('assets/css/rdta/rdta-bundled.css')
        .pipe(print())
        .pipe(sourcemaps.init())
        .pipe(cleanCSS({
            level: {
                1: {
                    specialComments: 0
                }
            }
        }))
        .pipe(rename('rdta-bundled.min.css'))
        .pipe(header(comment))
        .pipe(sourcemaps.write('.'))
        .pipe(dest('assets/css/rdta/'))
        .pipe(print());
}// minifyRDTACss


/**
 * Minify RDTA JS files.
 */
function minifyRDTAJs(cb) {
    const rename = require("gulp-rename");
    const uglify = require('gulp-uglify-es').default;
    const header = require('gulp-header');

    var comment = '/*! Rundiz Template for Admin*/\n\n';

    return src(jsToConcat, {
            base: 'node_modules/'
        })
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(concat('rdta-bundled.js'))
        .pipe(uglify({
            output: {
                comments: false
            }
        }))
        .pipe(header(comment))
        .pipe(rename(function (path) {
            path.basename += ".min";
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(dest('assets/js/rdta/'))
        .pipe(print());
}// minifyRDTAJs


exports.bundleAndMinify = series(
    bundleRDTA,
    minifyRDTAJs,
    minifyRDTACss
);