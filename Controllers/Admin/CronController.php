<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Cron job page controller.
 * 
 * @since 0.1
 */
class CronController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    /**
     * Cron job page.
     * 
     * Accept query string 'silence' value is 'true' or 'false' (default is 'false').
     * 
     * @return string If silence query string is 'true' then it will return empty string.
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $Cron = new \Rdb\Modules\RdbAdmin\Libraries\Cron($this->Container);

        $output = [];
        $output['runnedJobs'] = $Cron->runJobsOnAllModules();

        unset($Cron);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->get('silence', 'false') === 'true') {
            unset($output);
            return '';
        } else {
            return $this->responseAcceptType($output);
        }
    }// indexAction


}
