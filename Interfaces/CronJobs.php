<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Interfaces;


/**
 * Cron jobs interface.
 * 
 * @since 0.1
 */
interface CronJobs
{


    /**
     * Class constructor.
     * 
     * @param \System\Container $Container The DI container class.
     * @param \Modules\RdbAdmin\Libraries\Cron $Cron The cron class to help check job had already run.
     */
    public function __construct(\System\Container $Container, \Modules\RdbAdmin\Libraries\Cron $Cron);


    /**
     * Execute a cron job.
     * 
     * @link https://github.com/dragonmantank/cron-expression Cron Expression class that will be use for check due time.
     * @return bool Return `true` if it had run, `false` if it was not run.
     */
    public function execute(): bool;


}
