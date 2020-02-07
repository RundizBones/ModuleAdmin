# Module folder structure for use with RdbAdmin module.

These folder are reserved name and needed if you want to use its feature with RdbAdmin module. These are additional from framework folder structure.

```
ModuleName/
    CronJobs/
    ModuleData/
        ModuleAdmin.php
    languages/
        email-messages/
            YourMessage.html
            YourMessage-th.html
        translations/
            modulename-th.mo
            modulename-th.po
```

### CronJobs folder
This folder contain cron jobs to run via web browser or cron job server depend on configuration file. Any cron job files must implements `\Rdb\Modules\RdbAdmin\Interfaces\CronJobs` or may extends `\Rdb\Modules\RdbAdmin\CronJobs\BaseCronJobs` instead.<br>
For more information, please read on **Modules/RdbAdmin/Interfaces/CronJobs.php** file.

You only need this folder if you want to use its feature.

#### Reserved file name
 * None

### ModuleData folder
This folder should contain **ModuleAdmin.php** file that implements `\Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin` in case that you want to use permission controls, register admin menu items.<br>
For more information, please read on **Modules/RdbAdmin/Interfaces/ModuleAdmin.php** file.

You only need this folder if you want to use its feature.

#### Reserved file name
 * ModuleAdmin.php

### languages folder
This folder is contain the translation messages for email and web page (.mo, .po).<br>
The sub folder for email message is in **email-messages** folder.<br>
The sub folder for web page translation message is in **translations** folder.<br>
The translation file must suffix with language locale in **config/[your env]/language.php**.

You only need this folder if you want to use its feature.

#### Reserved file name
 * None.