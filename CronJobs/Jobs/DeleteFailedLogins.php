<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\CronJobs\Jobs;


/**
 * Delete failed logins log.
 * 
 * @since 0.1
 */
class DeleteFailedLogins
{


    /**
     * Execute the job.
     * 
     * @param \System\Libraries\Db $Db The Database class.
     */
    public static function execute(\System\Libraries\Db $Db)
    {
        $keepLoginsForDays = 90;// keep for xx days.
        $sql = 'DELETE FROM `' . $Db->tableName('user_logins') . '` WHERE `userlogin_result` = 0 AND `userlogin_date_gmt` < :userlogin_date_gmt';
        $Sth = $Db->PDO()->prepare($sql);
        unset($sql);

        $DateTimeUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $DateTimeUtc->sub(new \DateInterval('P' . $keepLoginsForDays . 'D'));

        $Sth->bindValue(':userlogin_date_gmt', $DateTimeUtc->format('Y-m-d H:i:s'));
        $Sth->execute();
        $Sth->closeCursor();
        unset($DateTimeUtc, $keepLoginsForDays, $Sth);
    }// execute



}
