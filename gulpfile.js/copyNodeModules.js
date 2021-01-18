/**
 * Copy files from node_modules to assets, assets-src folder.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
const print = require('gulp-print').default;


/**
 * Copy Datatables.
 */
function copyDataTables(cb) {
    const mergeStream =   require('merge-stream');

    return mergeStream(
        // images.
        src('node_modules/datatables.net-dt/images/**')
            .pipe(print())
            .pipe(dest('assets/vendor/datatables.net/images/')),
        // license file.
        src('node_modules/datatables.net/License.txt')
            .pipe(dest('assets/vendor/datatables.net/')),
        // pagination
        src('node_modules/datatables.net-plugins/pagination/**')
            .pipe(print())
            .pipe(dest('assets/vendor/datatables.net/plugins/pagination/'))
    );
}// copyDataTables


/**
 * Copy FontAwesome.
 */
function copyFontAwesome(cb) {
    const rename = require("gulp-rename");
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/@fortawesome/fontawesome-free/webfonts/**')
            .pipe(print())
            .pipe(dest('assets/css/webfonts/')),
        src('node_modules/@fortawesome/fontawesome-free/LICENSE.txt')
            .pipe(rename('fontawesome-license.txt'))
            .pipe(dest('assets/vendor/'))
    );
}// copyFontAwesome


/**
 * Copy Handlebars.
 */
function copyHandlebars(cb) {
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/handlebars/dist/**')
            .pipe(print())
            .pipe(dest('assets/vendor/handlebars/')),
        src('node_modules/handlebars/LICENSE')
            .pipe(dest('assets/vendor/handlebars/')),
    );
}// copyHandlebars


/**
 * Copy jQuery.
 */
function copyjQuery(cb) {
    const rename = require('gulp-rename');
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/jquery/LICENSE.txt')
            .pipe(rename('jquery-license.txt'))
            .pipe(dest('assets/vendor/'))
    );
}// copyjQuery


/**
 * Copy lodash
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function copyLodash(cb) {
    const mergeStream =   require('merge-stream');

    return mergeStream(
        // lodash.js files.
        src('node_modules/lodash/lodash*.js')
            .pipe(print())
            .pipe(dest('assets/vendor/lodash/')),
        // lodash.js license file.
        src('node_modules/lodash/LICENSE')
            .pipe(dest('assets/vendor/lodash/'))
    );
}// copyLodash


/**
 * Copy moment.js.
 */
function copyMoment(cb) {
    const mergeStream =   require('merge-stream');

    return mergeStream(
        // moment.js license file.
        src('node_modules/moment/LICENSE')
            .pipe(dest('assets/vendor/moment/')),
        // moment.js timezone plugin license file.
        src('node_modules/moment-timezone/LICENSE')
            .pipe(dest('assets/vendor/moment/timezone/')),
    );
}// copyMoment


/**
 * Copy Rundiz Template for Admin with its dependencies.
 */
function copyRDTA(cb) {
    const rename = require("gulp-rename");
    const mergeStream =   require('merge-stream');

    return mergeStream(
        // licenses.
        src('node_modules/rundiz-template-for-admin/assets/css/sanitize/LICENSE.md')
            .pipe(rename('sanitize-license.md'))
            .pipe(print())
            .pipe(dest('assets/vendor/')),
        src('node_modules/popper.js/README*')
            .pipe(rename('popper-readme.md'))
            .pipe(print())
            .pipe(dest('assets/vendor/')),
        src('node_modules/rundiz-template-for-admin/assets/js/smartmenus/LICENSE*')
            .pipe(rename('smartmenus-license.txt'))
            .pipe(print())
            .pipe(dest('assets/vendor/')),
        src('node_modules/rundiz-template-for-admin/assets/js/sticky-sidebar/LICENSE.md')
            .pipe(rename('sticky-sidebar-license.md'))
            .pipe(print())
            .pipe(dest('assets/vendor/')),
        src('node_modules/rundiz-template-for-admin/assets/js/resize-sensor/ResizeSensor-license')
            .pipe(rename('resizesensor-license.txt'))
            .pipe(print())
            .pipe(dest('assets/vendor/')),
    );
}// copyRDTA


/**
 * Copy sortableJS
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function copySortableJS(cb) {
    const mergeStream =   require('merge-stream');

    return mergeStream(
        // js files.
        src('node_modules/sortablejs/*.js')
            .pipe(print())
            .pipe(dest('assets/vendor/sortablejs/')),
        src('node_modules/sortablejs/modular/**')
            .pipe(print())
            .pipe(dest('assets/vendor/sortablejs/modular/')),
        // license file.
        src('node_modules/sortablejs/LICENSE')
            .pipe(dest('assets/vendor/sortablejs/'))
    );
}// copySortableJS


exports.copyNodeModules = parallel(
    copyDataTables,
    copyFontAwesome,
    copyHandlebars,
    copyjQuery,
    copyLodash,
    copyMoment,
    copyRDTA,
    copySortableJS
);