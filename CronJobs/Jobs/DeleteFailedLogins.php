<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs\Jobs;


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
     * @param \Rdb\System\Container $Container The DI Container class.
     * @param \Rdb\System\Libraries\Db $Db The Database class.
     */
    public static function execute(\Rdb\System\Container $Container, \Rdb\System\Libraries\Db $Db)
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($Container);
        $keepLoginsForDays = $ConfigDb->get('rdbadmin_UserLoginLogsKeep', 90);
        unset($ConfigDb);
        if (empty($keepLoginsForDays) || !is_numeric($keepLoginsForDays) || $keepLoginsForDays <= 0) {
            $keepLoginsForDays = 90; 
        }

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
