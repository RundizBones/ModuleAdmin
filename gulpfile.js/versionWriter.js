/**
 * Version writer.
 * 
 * Write updated version to PHP file.
 */


'use strict';


const {dest, series, src} = require('gulp');


/**
 * Get regular expression pattern.
 * 
 * @param {string} handleName
 * @returns {string}
 */
function getRegexPattern(handleName) {
    return '(\\['
        + '\\s*)'// group1
        + '([\\\'"]handle[\\\'"]\\s*=>\\s*[\\\'"]' + handleName + '[\\\'"],*\\s*)'// group2, 'handle' => 'xxx'
        + '([\\S\\s]+?version[\\\'"]\\s*=>\\s*[\\\'"])'// group3, anything and follow with quote or double quotes
        + '(.+)'// group4, version number
        + '([\\\'"],*)'// group5, end quote or double quotes
        + '(\\s*'
        + '\\])';// group6
}// getRegexPattern


/**
 * Get version number only (x.x.x).
 * 
 * Remove anything that is not dot, numbers.
 * 
 * @param {string} versionString
 * @returns {string}
 */
function getVersionNumberOnly(versionString) {
    return versionString.replace(/[^0-9.\-a-z]/ig, '');
}// getVersionNumberOnly


/**
 * Write packages version to PHP file.
 */
function writePackagesVersion(cb) {
    const fs = require('fs');
    const mergeStream =   require('merge-stream');
    const rename = require('gulp-rename');
    const replace = require('gulp-replace');

    let packageJson = JSON.parse(fs.readFileSync('./package.json'));
    let packageDependencies = (typeof(packageJson.dependencies) !== 'undefined' ? packageJson.dependencies : {});

    let datatablesVersion = packageDependencies['datatables.net-dt'];
    let handlebarsVersion = packageDependencies.handlebars;
    let rdtaVersion = packageDependencies['rundiz-template-for-admin'];
    let momentJsVersion = packageDependencies['moment'];
    let lodashVersion = packageDependencies['lodash'];
    let sortableJsVersion = packageDependencies['sortablejs'];

    packageJson = undefined;
    packageDependencies = undefined;

    let phpFile = './ModuleData/ModuleAssets.php';
    let targetFolder = './ModuleData/';
    let tasks = [];

    // make backup
    let date = new Date();
    let timeStampInMs = date.getFullYear() + ('0' + (date.getMonth() + 1)).slice(-2) + ('0' + date.getDate()).slice(-2)
        + '_' + ('0' + date.getHours()).slice(-2) + ('0' + date.getMinutes()).slice(-2) + ('0' + date.getSeconds()).slice(-2)
        + '_' + date.getMilliseconds();
    tasks[0] = src(phpFile)
        .pipe(rename('ModuleAssets.backup' + timeStampInMs + '.php'))
        .pipe(dest('.backup/ModuleData/'));

    tasks[1] = src(phpFile);
    if (typeof(datatablesVersion) !== 'undefined') {
        datatablesVersion = getVersionNumberOnly(datatablesVersion);
        let regExp = new RegExp(getRegexPattern('datatables'), 'gi');
        let regExp2 = new RegExp(getRegexPattern('datatables\\-plugins\\-pagination'), 'gi');
        tasks[1].pipe(replace(regExp, '$1$2$3' + datatablesVersion + '$5$6'))
            .pipe(replace(regExp2, '$1$2$3' + datatablesVersion + '$5$6'));
        datatablesVersion = undefined;
    }

    if (typeof(handlebarsVersion) !== 'undefined') {
        handlebarsVersion = getVersionNumberOnly(handlebarsVersion);
        let regExp = new RegExp(getRegexPattern('handlebars'), 'gi');
        tasks[1].pipe(replace(regExp, '$1$2$3' + handlebarsVersion + '$5$6'));
        handlebarsVersion = undefined;
    }

    if (typeof(rdtaVersion) !== 'undefined') {
        rdtaVersion = getVersionNumberOnly(rdtaVersion);
        let regExp = new RegExp(getRegexPattern('rdta'), 'gi');
        tasks[1].pipe(replace(regExp, '$1$2$3' + rdtaVersion + '$5$6'))
        rdtaVersion = undefined;
    }

    if (typeof(momentJsVersion) !== 'undefined') {
        momentJsVersion = getVersionNumberOnly(momentJsVersion);
        let regExp = new RegExp(getRegexPattern('moment.js'), 'gi');
        tasks[1].pipe(replace(regExp, '$1$2$3' + momentJsVersion + '$5$6'))
        momentJsVersion = undefined;
    }

    if (typeof(lodashVersion) !== 'undefined') {
        lodashVersion = getVersionNumberOnly(lodashVersion);
        let regExp = new RegExp(getRegexPattern('lodash'), 'gi');
        tasks[1].pipe(replace(regExp, '$1$2$3' + lodashVersion + '$5$6'))
        lodashVersion = undefined;
    }

    if (typeof(sortableJsVersion) !== 'undefined') {
        sortableJsVersion = getVersionNumberOnly(sortableJsVersion);
        let regExp = new RegExp(getRegexPattern('sortableJS'), 'gi');
        tasks[1].pipe(replace(regExp, '$1$2$3' + sortableJsVersion + '$5$6'))
        sortableJsVersion = undefined;
    }

    tasks[1].pipe(dest(targetFolder));

    return mergeStream(tasks);
}// writePackagesVersion


exports.writeVersions = series(
    writePackagesVersion
);