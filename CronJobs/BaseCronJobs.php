<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\CronJobs;


/**
 * Base cron job.
 * 
 * @since 0.1
 */
abstract class BaseCronJobs implements \Modules\RdbAdmin\Interfaces\CronJobs
{


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * @var \Modules\RdbAdmin\Libraries\Cron
     */
    protected $Cron;


    /**
     * @var \System\Libraries\Db
     */
    protected $Db;


    /**
     * {@inheritDoc}
     */
    public function __construct(\System\Container $Container, \Modules\RdbAdmin\Libraries\Cron $Cron)
    {
        $this->Container = $Container;
        $this->Cron = $Cron;

        if ($this->Container->has('Db')) {
            $this->Db = $this->Container->get('Db');
        }
    }// __construct


}
