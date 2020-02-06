<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\CronJobs\Jobs;


/**
 * Delete device cookie lockout list that was expired.
 * 
 * @since 0.1
 */
class DeleteExpiredDcLockout
{


    /**
     * Execute the job.
     * 
     * @param \System\Libraries\Db $Db The Database class.
     */
    public static function execute(\System\Libraries\Db $Db)
    {
        $sql = 'DELETE FROM `' . $Db->tableName('user_logins') . '` WHERE `userlogin_result` = 0 AND `userlogin_dc_lockout_until_gmt` < :userlogin_dc_lockout_until_gmt';
        $Sth = $Db->PDO()->prepare($sql);
        unset($sql);

        $Sth->bindValue(':userlogin_dc_lockout_until_gmt', gmdate('Y-m-d H:i:s'));
        $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);
    }// execute


}
