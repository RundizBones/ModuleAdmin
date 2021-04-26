<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs\Jobs;


/**
 * Delete old logs that was created more than n days.
 * 
 * @since 1.1.7
 */
class DeleteOldLogs
{


    /**
     * Execute the job.
     * 
     * @param \Rdb\System\Container $Container The DI Container class.
     * @param \Rdb\System\Libraries\Db $Db The Database class.
     */
    public static function execute(\Rdb\System\Container $Container, \Rdb\System\Libraries\Db $Db)
    {
        if ($Container->has('Config')) {
            /* @var $Config \Rdb\System\Config */
            $Config = $Container->get('Config');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $Config->setModule('RdbAdmin');

        $autoDeleteDays = (int) $Config->get('autoDeleteLogsAfterDays', 'cron', 90);
        // restore config module
        $Config->setModule('');
        unset($Config);

        if ($autoDeleteDays <= 0) {
            // if config was set to 0 or less.
            // do nothing here.
            unset($autoDeleteDays);
            return false;
        }

        if ($Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $Container->get('Logger');
        }

        $targetTimestamp = strtotime('-' . $autoDeleteDays . ' days');
        unset($autoDeleteDays);

        $FileSystem = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs');
        $files = $FileSystem->listFilesSubFolders('');

        if (is_array($files) && !is_bool($targetTimestamp)) {
            foreach ($files as $file) {
                $filemtime = $FileSystem->getTimestamp($file);

                if ($filemtime !== false && $filemtime < $targetTimestamp) {
                    // if file modified timestamp is older (less) than target deletion date.
                    // try to delete it.
                    if ($FileSystem->isFile($file)) {
                        $deleteResult = $FileSystem->deleteFile($file);

                        if (isset($Logger)) {
                            if (false === $deleteResult) {
                                $logLevel = 3;
                            } else {
                                $logLevel = 0;
                            }

                            $Logger->write(
                                'rdb/modules/rdbadmin/cronjobs/jobs/deleteoldlogs', 
                                $logLevel, 
                                'Delete log file: {file}; result: {result}', 
                                [
                                    'file' => STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $file,
                                    'result' => var_export($deleteResult, true),
                                ]
                            );
                            unset($logLevel);
                        }// endif $Logger

                        unset($deleteResult);
                    }// endif is file
                }// endif; modified timestamp is older than target date.

                unset($filemtime);
            }// endforeach;
            unset($file);
        }

        unset($FileSystem, $Logger, $targetTimestamp);
    }// execute


}
