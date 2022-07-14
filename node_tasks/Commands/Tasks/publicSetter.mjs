/**
 * Framework's public path setter.
 * 
 * Get, or find the framework's public path and update to global variable.
 */


'use strict';


import {exec, execSync} from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
// import this app's useful class.
import TextStyles from '../../Libraries/TextStyles.mjs';


export const publicSetter = class PublicSetter {


    /**
     * Update framework's public path to `rdbPublicModuleAssetsDir` global variable.
     * 
     * @async
     * @param {Object} argv The CLI arguments.
     */
    static async updatePublicPath(argv) {
        console.log(TextStyles.taskHeader('Update framework\'s public path.'));

        if (typeof(argv.public) === 'string' && argv.public !== '') {
            let publicPath = argv.public;
            publicPath = path.resolve(publicPath);// trim trailing slash.
            publicPath = publicPath.replace(/\\/g, '/');// normalize path for glob. replace \ to /
            publicPath = publicPath.replace(/\"|\'$/, '');// trim quote(s) at end.
            global.rdbPublicModuleAssetsDir = publicPath + '/' + moduleAssetsDir;
            console.log('  re-assigned global.rdbPublicModuleAssetsDir from `--public` argument: "', rdbPublicModuleAssetsDir + '".');
        } else {
            const commandTxt = 'php rdb system:constants --name="PUBLIC_PATH"';
            const frameworkPathFromRelative = path.resolve('../../');
            const fullPathToRdb = path.resolve(frameworkPathFromRelative, 'rdb');
            
            if (!fs.existsSync(fullPathToRdb)) {
                console.error(TextStyles.txtError('Error: The rdb file is not exists (' + fullPathToRdb + '). Please manually set `--public` option with full path to framework\'s public folder.'));
                process.exit(1);
            }

            await exec(commandTxt,
                {
                    cwd: frameworkPathFromRelative
                }, 
                (err, stdout, stderr) => {
                // the regular expression pattern of php constant has got from https://www.php.net/manual/en/language.constants.php
                const regex = /^([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)(\s+)[=](\s+)(.+)$/im;
                let m;
    
                if ((m = regex.exec(stdout)) !== null) {
                    // The result can be accessed through the `m`-variable.
                    if (typeof(m[4]) === 'string') {
                        global.rdbPublicModuleAssetsDir = m[4] + '/' + moduleAssetsDir;
                        console.log('  re-assigned global.rdbPublicModuleAssetsDir: "', rdbPublicModuleAssetsDir + '".');
                    }
                }
            });
        }

        console.log('End update framework\'s public path.');
    }// updatePublicPath


}