<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs;


/**
 * Base cron job.
 * 
 * @since 0.1
 */
abstract class BaseCronJobs implements \Rdb\Modules\RdbAdmin\Interfaces\CronJobs
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Cron
     */
    protected $Cron;


    /**
     * @var \Rdb\System\Libraries\Db
     */
    protected $Db;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container, \Rdb\Modules\RdbAdmin\Libraries\Cron $Cron)
    {
        $this->Container = $Container;
        $this->Cron = $Cron;

        if ($this->Container->has('Db')) {
            $this->Db = $this->Container->get('Db');
        }
    }// __construct


}
