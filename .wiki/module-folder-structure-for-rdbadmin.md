# Module folder structure for use with RdbAdmin module.

These folder are reserved name and needed if you want to use its feature with RdbAdmin module. These are additional from framework folder structure.

```
ModuleName/
    CronJobs/
    ModuleData/
        ModuleAdmin.php
    Plugins/
    languages/
        email-messages/
            YourMessage.html
            YourMessage-th.html
        translations/
            modulename-th.mo
            modulename-th.po
    Installer.php
```

## CronJobs folder
This folder contain cron jobs to run via web browser or cron job server depend on configuration file. Any cron job files must implements `\Rdb\Modules\RdbAdmin\Interfaces\CronJobs` or may extends `\Rdb\Modules\RdbAdmin\CronJobs\BaseCronJobs` instead.<br>
For more information, please read on **Modules/RdbAdmin/Interfaces/CronJobs.php** file.

You only need this folder if you want to use its feature.

### Reserved file name
 * None

## ModuleData folder
This folder should contain **ModuleAdmin.php** file that implements `\Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin` in case that you want to use permission controls, register admin menu items.<br>
For more information, please read on **Modules/RdbAdmin/Interfaces/ModuleAdmin.php** file.

You only need this folder if you want to use its feature.

### Reserved file name
 * ModuleAdmin.php

## Plugins folder
This folder is for store any plugins that can be hook into any actions, alters. Each of plugin must has its own folder and PHP file.<br>
For example, You have more than one plugins in a single module and their names are **UserHooks**, **SettingHooks**.<br>
The PHP file of each plugin should be **UserHooks/UserHooks.php**, **SettingHooks/SettingHooks.php**.

Any plugin files must implements `\Rdb\Modules\RdbAdmin\Interfaces\Plugins`.<br>
For more information, please read on **Modules/RdbAdmin/Interfaces/Plugins.php** file.

You only need this folder if you want to use its feature.

### Reserved file name
 * None

## languages folder
This folder is contain the translation messages for email and web page (.mo, .po).<br>
The sub folder for email message is in **email-messages** folder.<br>
The sub folder for web page translation message is in **translations** folder.<br>
The translation file must suffix with language locale in **config/[your env]/language.php**.

You only need this folder if you want to use its feature.

### Reserved file name
 * None.

## [Installer.php](module-installer.md)