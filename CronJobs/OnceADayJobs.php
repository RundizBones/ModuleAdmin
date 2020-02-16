<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs;


/**
 * Cron jobs that run once a day.
 * 
 * @since 0.1
 */
class OnceADayJobs extends BaseCronJobs
{


    /**
     * {@inheritDoc}
     */
    public function execute(): bool
    {
        // crontab format is 'minute' 'hour' 'day of month' 'month' 'day of week'
        // 0-15 for minute unit is start at 0 minute but can delay for 15 minutes.
        $CronExpression = new \Cron\CronExpression('0-15 0 * * *', new \Cron\FieldFactory());

        if ($CronExpression->isDue() && !$this->Cron->hasRun()) {
            // if cron job is due and never run before.
            // execute the jobs in Jobs folder.
            Jobs\DeleteExpiredDcLockout::execute($this->Db);
            Jobs\DeleteExpiredLogins::execute($this->Db);
            Jobs\DeleteExpiredUserRegisterWaitConfirm::execute($this->Container, $this->Db);
            Jobs\DeleteFailedLogins::execute($this->Container, $this->Db);
            Jobs\DeleteSoftDeleteUsers::execute($this->Container, $this->Db);

            // write cache that this job had already run.
            $cacheTTL = $this->Cron->getSecondsBeforeNext();
            $this->Cron->cacheHadRun($cacheTTL);
            unset($cacheTTL);

            unset($CronExpression);
            return true;
        }// endif isDue()

        unset($CronExpression);
        return false;
    }// execute


}
