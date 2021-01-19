<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Cron class.
 * 
 * @since 0.1
 */
class Cron
{


    /**
     * @var \Psr\SimpleCache\CacheInterface 
     */
    protected $Cache;


    /**
     * @var string The cache based folder.
     */
    protected $cacheBasedPath = STORAGE_PATH . '/cache/Modules/RdbAdmin/Libraries/Cron';


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        $this->Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            ['cachePath' => $this->cacheBasedPath]
        ))->getCacheObject();
    }// __construct


    /**
     * Set cache that cron job had already run.
     * 
     * @param int|\DateInterval $ttl The expire date/time.
     *      This should related to your cron job time.
     *      For example: Your cron job run every hour (0 minute, 0 second) then this should be ((next hour timestamp in 0 minute 0 second - 1 minute) - current timestamp),
     *      Your cron job run every day (0 midnight, 0 minute, 0 second) then this should be ((next day timestamp in 0 hour 0 minute 0 second - 1 minute) - current timestamp)
     * @return bool Return `true` on success write the cache, `false` for otherwise.
     */
    public function cacheHadRun($ttl): bool
    {
        return $this->Cache->set($this->getCallerClass(), true, $ttl);
    }// cacheHadRun


    /**
     * Get caller class.
     * 
     * @return string Return class or file name.
     */
    protected function getCallerClass(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $return = '';

        if (is_array($trace)) {
            if (isset($trace[2]['class']) && is_string($trace[2]['class'])) {
                $return = $trace[2]['class'];
            } elseif (isset($trace[2]['file']) && is_string($trace[2]['file'])) {
                $return = $trace[2]['file'];
            }
        }

        unset($trace);

        return $return;
    }// getCallerClass


    /**
     * Get seconds before next run date/time and maybe subtract 1 minute, 10 seconds (for unit in minute).
     * 
     * @param string $unit Accepted unit is 'minute', 'hour', 'day'
     * @param bool $subtract Set to `true` (default) to subtract few time before next date/time. This is for allow it to be expired before time. Set to `false` to not subtract.
     * @return int Return number of seconds minus 1 minute before next run date/time.
     * @throws Throw the errors if unknown unit argument was set.
     */
    public function getSecondsBeforeNext(string $unit = 'day', bool $subtract = true): int
    {
        $DateTime = new \DateTime();
        $currentTimestamp = $DateTime->format('U');
        $return = 0;

        if ($unit === 'minute') {
            $DateTime->add(new \DateInterval('PT1M'));
            $DateTime->setTime($DateTime->format('H'), $DateTime->format('i'), 0);
            if ($subtract === true) {
                $DateTime->sub(new \DateInterval('PT10S'));// subtract 10 seconds before next run.
            }
        } elseif ($unit === 'hour') {
            $DateTime->add(new \DateInterval('PT1H'));
            $DateTime->setTime($DateTime->format('H'), 0, 0);
            if ($subtract === true) {
                $DateTime->sub(new \DateInterval('PT1M'));// subtract 1 minute before next run.
            }
        } elseif ($unit === 'day') {
            $DateTime->add(new \DateInterval('P1D'));
            $DateTime->setTime($DateTime->format('H'), 0, 0);
            if ($subtract === true) {
                $DateTime->sub(new \DateInterval('PT1M'));// subtract 1 minute before next run.
            }
        } else {
            throw new \DomainException('Unknown unit argument.');
        }

        $nextRunTimestamp = $DateTime->format('U');
        $return = (int) ($nextRunTimestamp - $currentTimestamp);
        unset($nextRunTimestamp);

        unset($currentTimestamp, $DateTime);
        return $return;
    }// getSecondsBeforeNext


    /**
     * Check that cron job has run or not.
     * 
     * @return bool Return `true` if it had already run, `false` for otherwise.
     */
    public function hasRun(): bool
    {
        $cacheName = $this->getCallerClass();
        if ($this->Cache->has($cacheName)) {
            $cacheResult = $this->Cache->get($cacheName, null);
            if (is_bool($cacheResult)) {
                return $cacheResult;
            }
            unset($cacheResult);
        }
        return false;
    }// hasRun


    /**
     * Check if cron is enabled.
     * 
     * @return bool Return `true` if enabled, `false` for not.
     */
    protected function isEnableCron(): bool
    {
        if ($this->Container->has('Config')) {
            /* @var $Config \Rdb\System\Config */
            $Config = $this->Container->get('Config');
            $Config->setModule('RdbAdmin');
            $enableCron = $Config->get('enableCron', 'cron', true);
            $Config->setModule('');// restore to default.
            unset($Config);

            if (is_bool($enableCron)) {
                return $enableCron;
            }
        }

        return false;
    }// isEnableCron


    /**
     * Run jobs on all enabled modules.
     * 
     * @return array Return associative array of runned cron job.
     */
    public function runJobsOnAllModules(): array
    {
        $output = [];

        if ($this->Container->has('Modules') && $this->isEnableCron() === true) {
            $cacheKey = __CLASS__;

            if (!$this->Cache->has($cacheKey)) {
                // if cron never run within range.
                /* @var $Modules \Rdb\System\Modules */
                $Modules = $this->Container->get('Modules');
                $enabledModules = $Modules->getModules();

                if (is_array($enabledModules)) {
                    $ReflectionClassTargetInstance = new \ReflectionClass('\\Rdb\\Modules\\RdbAdmin\\Interfaces\\CronJobs');
                    if ($this->Container->has('Logger')) {
                        /* @var $Logger \Rdb\System\Libraries\Logger */
                        $Logger = $this->Container->get('Logger');
                    }

                    foreach ($enabledModules as $moduleSystemName) {
                        $moduleCronFolder = MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName . DIRECTORY_SEPARATOR . 'CronJobs';

                        if (is_dir($moduleCronFolder)) {
                            $RecurItIt = new \RecursiveIteratorIterator(
                                new \RecursiveDirectoryIterator(
                                    $moduleCronFolder, 
                                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
                                ), 
                                \RecursiveIteratorIterator::CHILD_FIRST
                            );

                            if (is_array($RecurItIt) || is_object($RecurItIt)) {
                                foreach ($RecurItIt as $filePath => $object) {
                                    if (is_file($filePath)) {
                                        $pathToClass = '\\Rdb' . str_replace([ROOT_PATH, '.php', '/'], ['', '', '\\'], $filePath);// from /Path/To/Class.php => \Rdb\Path\To\Class

                                        if (class_exists($pathToClass)) {
                                            $ReflectionCronJob = new \ReflectionClass($pathToClass);

                                            if (!$ReflectionCronJob->isAbstract()) {
                                                $cronInstance = $ReflectionCronJob->newInstanceWithoutConstructor();

                                                if ($ReflectionClassTargetInstance->isInstance($cronInstance)) {
                                                    $CronJob = $ReflectionCronJob->newInstance($this->Container, $this);
                                                    $runResult = $CronJob->execute();
                                                    $resultArray = [
                                                        'file' => $filePath,
                                                        'class' => $ReflectionCronJob->getName(),
                                                        'phpSAPI' => PHP_SAPI,
                                                        'runResult' => $runResult,
                                                    ];

                                                    if (isset($Logger) && $runResult === true) {
                                                        $Logger->write('modules/rdbadmin/libraries/cron', 0, 'cron job ' . $pathToClass . ' has run.', $resultArray);
                                                    }

                                                    $output[] = $resultArray;

                                                    unset($CronJob, $resultArray, $runResult);
                                                }
                                            }

                                            unset($cronInstance, $ReflectionCronJob);
                                        }// endif class exists $pathToClass

                                        unset($pathToClass);
                                    }// endif; is file $filePath
                                }// endforeach;
                                unset($filePath, $object);
                            }// endif $RecurItIt

                            unset($RecurItIt);
                        }// endif; cron folder exists.

                        unset($moduleCronFolder);
                    }// endforeach;
                    unset($moduleSystemName, $ReflectionClassTargetInstance);
                }// endif; $enabledModules

                unset($enabledModules, $Modules);

                // it was already just run, set cache to skip it.
                $this->Cache->set($cacheKey, true, $this->getSecondsBeforeNext('minute', false));
            }// endif; cache (cron never run within range).

            unset($cacheKey);
        }// endif container has Modules

        return $output;
    }// runJobsOnAllModules


}
