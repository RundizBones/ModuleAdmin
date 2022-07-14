/**
 * Node tasks for:
 *   Build (clear assets folder on public path, copy/bundle/minify Node packages to public path, copy/bundle/minify assets to public path).
 *   Watch (copy assets to public path).
 *   Write versions (write Node package's versions to module data in PHP file).
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


'use strict';


import {fileURLToPath} from 'node:url';
import path from 'node:path';
// yargs. -------------------------------------
import yargs from 'yargs/yargs';
import {hideBin} from 'yargs/helpers';
const yargv = yargs(hideBin(process.argv));
// yargs. -------------------------------------
// import this app's useful class.
import NtConfig from './Libraries/ntConfig.mjs';
// import main entry of all commands.
import {commands} from './Commands/index.mjs';


const __filename = fileURLToPath(import.meta.url);
// define full path to this RundizBones module main folder.
global.MODULE_DIR = path.dirname(path.dirname(__filename));
// define full path to this node_tasks folder.
global.NODETASKS_DIR = path.dirname(__filename);

global.moduleAssetsDir = NtConfig.getModuleAssetsDir();
global.rdbPublicModuleAssetsDir = '../../public/' + moduleAssetsDir;


yargv
.command(commands)
.demandCommand()
.help()
.argv
;