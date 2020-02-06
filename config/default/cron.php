<?php
/** 
 * Cron job configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


return [
    // enable cron job or not. Set to `true` to enable, `false` to disable at all.
    'enableCron' => true,
    // use server cron or run via HTTP that is included in the base controller. set to `true` to use server cron, `false` for HTTP.
    // if `enableCron` is set to `false` then this will never work.
    // it is recommended to use server cron to improve performance on web based.
    // if use server cron:
        // please set task to:
            // Windows task scheduler with Powershell: `Invoke-WebRequest http://yourdomain/admin/cron`
            // Windows task scheduler with CURL: `curl http://yourdomain/admin/cron`
            // any OS via command line: `php rdb rdba:cron`
        // please set crontab times on the server to every minute or hour.
            // every minute * * * * *
            // every hour 0 * * * *
            // every day 0 0 * * *
    'useServerCron' => false,
];