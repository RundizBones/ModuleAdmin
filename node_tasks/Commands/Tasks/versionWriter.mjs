/**
 * Module assets version writer.
 */


'use strict';


import {exec, execSync} from 'node:child_process';
import fs from 'node:fs';
import fsPromise from 'node:fs/promises';
import path from 'node:path';
// import this app's useful class.
import FS from '../../Libraries/FS.mjs';
import NtConfig from '../../Libraries/NtConfig.mjs';
import TextStyles from '../../Libraries/TextStyles.mjs';


const PHPFile = 'ModuleData/ModuleAssets.php';


export const versionWriter = class VersionWriter {


    /**
     * @type {Object} The CLI arguments.
     */
    argv;


    /**
     * @type {string} Full path to module assets data in PHP file. This property will be set in non static `init()` method.
     */
    fullPathModuleAssetsData;


    /**
     * Class constructor.
     * 
     * @param {Object} argv The CLI arguments.
     */
    constructor(argv) {
        if (typeof(argv) === 'object') {
            this.argv = argv;
        }
    }// constructor


    /**
     * @returns {string} PHP file. Related from this RundizBones module folder.
     */
    get PHPFile() {
        return PHPFile;
    }// PHPFile


    /**
     * Create backup for PHP file.
     * 
     * @private This method was called from `init()`.
     */
    #createPHPBackup() {
        console.log('  Create backup for PHP file.');
        const date = new Date();
        const timeStampInMs = date.getFullYear() + ('0' + (date.getMonth() + 1)).slice(-2) + ('0' + date.getDate()).slice(-2)
            + '_' + ('0' + date.getHours()).slice(-2) + ('0' + date.getMinutes()).slice(-2) + ('0' + date.getSeconds()).slice(-2)
            + '_' + date.getMilliseconds();
        const backupFilenameTemplate = 'ModuleAssets.backup%TS%.php';
        const backupDestination = '.backup/ModuleData/' + backupFilenameTemplate.replace('%TS%', timeStampInMs);

        // copy source file to destination to create backup.
        const destination = path.resolve(MODULE_DIR, backupDestination);
        FS.copyFileDir(this.fullPathModuleAssetsData, destination);
        
        console.log('    Backup was created in ' + destination);
        console.log('  End create backup for PHP file.');
        return Promise.resolve();
    }// createPHPBackup


    /**
     * Get installed Node packages.
     * 
     * @private This method was called from `init()`.
     * @returns {Object} Return JSON object.
     */
    #getInstalledPackages() {
        console.log('  Get installed Node packages.');

        const result = execSync(
            'npm ls --depth=0 --omit=dev --json',
            {
                cwd: MODULE_DIR,
            }
        );

        const resultObj = JSON.parse(result.toString());
        const dependencies = (resultObj?.dependencies ?? {});

        console.log('    Found total ' + Object.entries(dependencies).length + ' items.');
        console.log('  End get installed Node packages.');

        return (typeof(dependencies) === 'object' ? dependencies : {});
    }// getInstalledPackages


    /**
     * Get regular expression pattern target on module assets data PHP file.
     * 
     * @private This method was called from `writeVersions()`.
     * @param {string} handleName The handle name.
     * @returns {string} Return regular expression pattern.
     */
    #getRegexPatternModulePHP(handleName) {
        return '(\\['
            + '\\s*)'// group1
            + '([\\\'"]handle[\\\'"]\\s*=>\\s*[\\\'"]' + handleName + '[\\\'"],*\\s*)'// group2, 'handle' => 'xxx'
            + '([\\S\\s]+?version[\\\'"]\\s*=>\\s*[\\\'"])'// group3, anything and follow with quote or double quotes
            + '(.+)'// group4, version number
            + '([\\\'"],*)'// group5, end quote or double quotes
            + '(\\s*'
            + '\\])';// group6
    }// getRegexPatternModulePHP


    /**
     * Get only valid version number from version string.
     * 
     * @private This method was called from `writeVersions()`.
     * @param {string} versionString Version string.
     * @returns {string} Return version string.
     */
    #getVersionNumber(versionString) {
        const regexPattern = /(?<version>([\d\.]+)([-+\.0-9a-z]*))/miu;
        const matched = versionString.match(regexPattern);

        if (matched && matched.groups && matched.groups.version) {
            return matched.groups.version;
        }

        return 'unknown';
    }// getVersionNumber


    /**
     * Initialize the class in non-static method.
     * 
     * @async
     * @private This method was called from `static init()`.
     */
    async #init() {
        const fullPathModuleAssetsData = path.resolve(MODULE_DIR, this.PHPFile);
        if (!fs.existsSync(fullPathModuleAssetsData)) {
            // if not found Module PHP assets data file.
            console.log('  ' + TextStyles.txtWarning('Not found module assets data file (' + fullPathModuleAssetsData + ').'));
            return Promise.resolve();
        }
        this.fullPathModuleAssetsData = fullPathModuleAssetsData;

        const packageDependencies = this.#getInstalledPackages();
        if (Object.entries(packageDependencies).length <= 0) {
            // if found no installed packages.
            console.warn('  ' + TextStyles.txtWarning('Not found any installed Node packages. Please verify again that there is `dependencies` in package.json and there is node_modules folder that contain installed packages.'));
            return Promise.resolve();
        }

        await this.#createPHPBackup();

        await this.#writeVersions(packageDependencies);

        return Promise.resolve();
    }// init


    /**
     * Initialize the class.
     * 
     * @async
     */
    static async init(argv) {
        console.log(TextStyles.taskHeader('Write versions.'))
        const thisClass = new this(argv);
        await thisClass.#init();

        console.log('End write versions.');
        return Promise.resolve();
    }// init

    
    /**
     * Write Node packages version to module PHP data file.
     * 
     * @async
     * @private This method was called from `init()`.
     * @param {object|object[]} packageDependencies The array list of installed packages.
     */
    async #writeVersions(packageDependencies) {
        if (typeof(packageDependencies) !== 'object') {
            throw new Error('The agument `packageDependencies` must be array "' + typeof(packageDependencies) + '" given.');
        }

        console.log('  Write Node packages version to module PHP data.');
        const writeVersionsCfg = NtConfig.getValue('writeVersions', []);

        const fh = await fsPromise.open(this.fullPathModuleAssetsData, 'r+');
        let moduleAssetsDataContents = await fh.readFile({encoding: 'utf8'});

        for (const [index, eachCfgPackage] of writeVersionsCfg.entries()) {
            if (typeof(eachCfgPackage?.nodePackage) !== 'string' || !eachCfgPackage.nodePackage) {
                // if not found `nodePackage` property in the config object.
                console.warn('    ' + TextStyles.txtWarning('Not found `nodePackage` property in `writeVersions` object (indexed ' + index + ') in the config.json file. Skipping.'));
                break;
            }

            if (typeof(packageDependencies[eachCfgPackage.nodePackage]) !== 'object') {
                // if not found this installed packages.
                console.warn('    ' + TextStyles.txtWarning('Not found installed package name "' + eachCfgPackage.nodePackage + '". Skipping.'));
                break;
            }

            if (
                typeof(eachCfgPackage.phpHandlesRegex) !== 'object' || 
                !Array.isArray(eachCfgPackage.phpHandlesRegex) || 
                Object.entries(eachCfgPackage.phpHandlesRegex).length <= 0
            ) {
                // if not found `phpHandlesRegex` property in the config object.
                // or it is not array
                // or it is empty array
                console.warn('    ' + TextStyles.txtWarning('The `phpHandlesRegex` property is not array or empty.'));
                break;
            }

            const installedVersion = this.#getVersionNumber(packageDependencies[eachCfgPackage.nodePackage].version);
            for (const [mhIndex, mhRegex] of eachCfgPackage.phpHandlesRegex.entries()) {
                // mh = Module assets data handle in PHP file.
                let regExp = new RegExp(this.#getRegexPatternModulePHP(mhRegex), 'gi');
                moduleAssetsDataContents = moduleAssetsDataContents.replace(regExp, '$1$2$3' + installedVersion + '$5$6');
                const foundMatched = moduleAssetsDataContents.match(regExp);
                if (foundMatched && typeof(foundMatched) === 'object' && foundMatched.length > 0) {
                    console.log('    Replaced version ' + installedVersion + ' for "' + mhRegex + '" handle.');
                } else {
                    console.warn('    ' + TextStyles.txtWarning('The handle "' + mhRegex + '" was not found in module assets data PHP. Couldn\'t replace version.'));
                }
            }// endfor; phpHandlesRegex
        }// endfor; writeVersionsCfg.entries()

        try {
            // must use file handle `.write()` with position 0 because `.writeFile()` will becomes append duplicated content.
            await fh.write(moduleAssetsDataContents, 0, 'utf8');
        } finally {
            await fh.close();
        }

        console.log('  End write Node packages version.');
        return Promise.resolve();
    }// writeVersions


}