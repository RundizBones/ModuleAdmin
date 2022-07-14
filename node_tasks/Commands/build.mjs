/**
 * yargs command: build.
 * 
 * Tasks for this command:
 * 1. Get and set framework's public path to `rdbPublicModuleAssetsDir` global variable.
 * 2. Delete assets folder & public/Modules/<this rdb module>/assets folder.
 * 3. Copy some Node packages that is no need to bundle/minify. Copy them to assets folder.
 * 4. Bundle, compile, minify::
 *  4.1. Bundle & minify some Node packages to assets folder.
 *  4.2. Copy (and maybe bundle, compile) assets-src folder to assets folder.
 * 5. Copy assets folder to public/Modules/<this rdb module>/assets.
 */


'use strict';


// import this app's useful class.
import TextStyles from '../Libraries/TextStyles.mjs';
// import tasks for this command.
import {assetsCopier} from './Tasks/assetsCopier.mjs';
import {assetsSrcCopier} from './Tasks/assetsSrcCopier.mjs';
import {bundler} from './Tasks/Build/bundler.mjs';
import {deleter} from './Tasks/Build/deleter.mjs';
import {npCopier} from './Tasks/Build/npCopier.mjs';
import {publicSetter} from './Tasks/publicSetter.mjs';


export const command = 'build';
export const describe = 'Build asset files such as CSS, JS, images and copy to public folder in the end.';
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
    .example('$0 build')
    .example('$0 build --public "/var/www/public_html"')
    .example('$0 build --public "/var/www/public_html" --nocppublic')
    ;// end .options;
};
export const handler = async (argv) => {
    console.log(TextStyles.programHeader());
    console.log(TextStyles.commandHeader(' Command: ' + argv._ + ' '));

    // 1. Get and set framework's public path to `rdbPublicModuleAssetsDir` global variable.
    await publicSetter.updatePublicPath(argv);
    // 2. Delete target folders. Basically they are assets folder or assets/vendor.
    await deleter.clean(argv);
    // 3. Copy some Node packages that is ready to use without bundle, minify.
    await npCopier.copy(argv);
    // 4. Bundle, compile, minify::
    await bundler.run(argv);
    // 4.2 Copy assets-src to assets.
    await assetsSrcCopier.run(argv);
    // 5. Copy assets folder to public.
    await assetsCopier.copy(argv);

    console.log(TextStyles.txtSuccess(TextStyles.taskHeader('End command.')));
};