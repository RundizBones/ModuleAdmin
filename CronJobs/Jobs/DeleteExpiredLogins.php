<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs\Jobs;


/**
 * Delete expired logins cron job.
 * 
 * @since 0.1
 */
class DeleteExpiredLogins
{


    /**
     * Execute the job.
     * 
     * @param \Rdb\System\Libraries\Db $Db The Database class.
     */
    public static function execute(\Rdb\System\Libraries\Db $Db)
    {
        $sql = 'DELETE FROM `' . $Db->tableName('user_logins') . '` WHERE `userlogin_result` = 1 AND `userlogin_expire_date_gmt` < :userlogin_expire_date_gmt';
        $Sth = $Db->PDO()->prepare($sql);
        unset($sql);

        $Sth->bindValue(':userlogin_expire_date_gmt', gmdate('Y-m-d H:i:s'));
        $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);
    }// execute


}
