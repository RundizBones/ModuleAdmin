/**
 * Pack folders and files into zip that is ready to use in distribute release.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
let argv = require('yargs').argv;
const fs = require('fs');
const print = require('gulp-print').default;
const zip = require('gulp-zip');


/**
 * Pack files for production or development zip.
 * 
 * To pack for development, run `gulp pack --development` or `npm run pack -- --development`.<br>
 * To pack for production, run `gulp pack` or `npm run pack`.
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function packDist(cb) {
    let installerPhpContent = fs.readFileSync('./Installer.php', 'utf-8');
    let regexPattern = /@version(\s?)(?<version>[\d\.]+)/miu;
    let matched = installerPhpContent.match(regexPattern);
    let moduleVersion = 'unknown';
    if (matched && matched.groups && matched.groups.version) {
        moduleVersion = matched.groups.version;
    }

    let isProduction = true;
    if (argv.development) {
        isProduction = false;
    }

    let targetDirs = [];
    if (isProduction === true) {
        targetDirs = [
            './**',
            '!assets-src/**',
            '!config/development/**',
            '!config/production/**',
            '!gulpfile.js/**',
            '!node_modules/**',
            '!Tests/**',
            '!mkdocs.yml',
            '!package*.json',
            '!phpdoc.xml',
            '!phpunit.xml',
            /*'Console/**', 
            'Controllers/**',
            'CronJobs/**',
            'Helpers/**',
            'Interfaces/**',
            'Libraries/**',
            'Models/**',
            'ModuleData/**',
            'Views/**',
            'assets/**',
            'config/**',
            'languages/**',
            'phinxdb/**',
            'Installer.php',
            'Installer.sql',
            'moduleComposer.json',*/
        ];
    } else {
        targetDirs = [
            './**',
            '.*/**',
            '!.backup/**',
            '!.git/**',
            '!.phpdoc/**',
            '!.dist/**',
            '!config/development/**',
            '!config/production/**',
            '!node_modules/**',
            '!package-*.json',
        ];
    }
    let zipFileName;
    if (isProduction === true) {
        zipFileName = 'RdbAdmin v' + moduleVersion + '.zip';
    } else {
        zipFileName = 'RdbAdmin dev.zip';
    }

    return src(targetDirs, { base : "." })
        .pipe(print())
        .pipe(zip(zipFileName))
        .pipe(dest('.dist/'));
}// packDist


exports.pack = series(
    packDist
);