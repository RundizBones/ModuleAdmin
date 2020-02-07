<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


/**
 * Extended cron class for tests.
 * 
 * @since 0.1
 */
class CronExtended extends \Rdb\Modules\RdbAdmin\Libraries\Cron
{


    /**
     * Assume that this method is `cacheHadRun()` or `hasRun()` method, the `getCallerClass()` method must return correct caller class.
     * 
     * @return string Return caller class.
     */
    public function checkCallerfromHasRun()
    {
        return $this->getCallerClass();
    }// checkCallerfromHasRun


}
