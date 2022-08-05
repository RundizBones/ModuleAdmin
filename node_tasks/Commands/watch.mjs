/**
 * yargs command: watch.
 * 
 * Tasks for this command:
 * 1. Get and set framework's public path to `rdbPublicModuleAssetsDir` global variable.
 * 2. Start watching::
 *  2.1. Copy (and maybe bundle, compile) assets-src folder to assets folder.
 *  2.2. Copy assets folder to public/Modules/<this rdb module>/assets.
 */


'use strict';


// import this app's useful class.
import TextStyles from '../Libraries/TextStyles.mjs';
// import tasks for this command.
import {watcher} from './Tasks/watcher.mjs';
import {publicSetter} from './Tasks/publicSetter.mjs';


export const command = 'watch';
export const describe = 'Watch asset files such as CSS, JS, images changed and maybe copy asset-src to assets folder first (depend on config file) then copy assets to public folder in the end.';
export const builder = (yargs) => {
    return yargs.options({
        'public': {
            demandOption: false,
            describe: 'The framework\'s public full path.',
            type: 'string',
        },
        'nocppublic': {
            demandOption: false,
            describe: 'Do not copy assets to framework\'s public folder.',
            type: 'boolean',
        },
    })
    .example('$0 watch')
    .example('$0 watch --public "/var/www/public_html"')
    .example('$0 watch --public "/var/www/public_html" --nocppublic')
    ;// end .options;
};
export const handler = async (argv) => {
    console.log(TextStyles.programHeader());
    console.log(TextStyles.commandHeader(' Command: ' + argv._ + ' '));

    // 1. Get and set framework's public path to `rdbPublicModuleAssetsDir` global variable.
    await publicSetter.updatePublicPath(argv);
    // 2. Start watching.
    const watcherObj = new watcher(argv);
    watcherObj.watch();
};