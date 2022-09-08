/**
 * Main entry of all commands.
 */


'use strict';


import * as buildcmd from './build.mjs';
import * as watchcmd from './watch.mjs';
import * as writeVersionCmd from './writeVersions.mjs';
import * as exprcmd from './expr.mjs';


export const commands = [
    //buildcmd,
    watchcmd,
    writeVersionCmd,
    exprcmd,
];