# RundizBones Admin module

RundizBones Admin is a management module for manage users, roles, permissions, and site settings. This module is working on RundizBones framework.

## Getting started
* [Getting started](getting-started.md).

If you want to start development using RdbAdmin module, please read following. Otherwise this is finished.

---

## Folder structure

* [Module folder structure](module-folder-structure-for-rdbadmin.md).
* [Public folder structure](public-folder-structure-for-rdbadmin.md).

## Controllers

* [Front end controller](frontend-controllers.md).
* [Admin (back-end) controller](admin-controllers.md).

## Models
* [Models](models.md).

## Views
* [Admin views](admin-views.md).

---

## Commands
RundizBones Admin module contain many commands to run in command line interface (**CLI**) such as Command Prompt, PowerShell, Terminal.<br>
Run `php rdb list` to see the list of all available commands. 
The commands that are from this module will be prefix with `rdbadmin:` for example: `rdbadmin:cron`.<br>
To see help for each command run `php rdb` and follow with that command and end with `--help`. Example: `php rdb rdbadmin:create-module --help`.