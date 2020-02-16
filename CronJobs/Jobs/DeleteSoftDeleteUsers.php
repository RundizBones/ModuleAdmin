<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs\Jobs;


/**
 * Delete the users that was mark as soft delete.
 * 
 * @since 0.1
 */
class DeleteSoftDeleteUsers
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
        $keepSoftDeleteForDays = $ConfigDb->get('rdbadmin_UserRegisterWaitVerification', 2);
        unset($ConfigDb);
        if (empty($keepSoftDeleteForDays) || !is_numeric($keepSoftDeleteForDays) || $keepSoftDeleteForDays <= 0) {
            $keepSoftDeleteForDays = 30; 
        }

        $sql = 'SELECT `user_id`, `user_deleted`, `user_deleted_since_gmt` 
            FROM `' . $Db->tableName('users') . '` 
            WHERE `user_deleted` = 1 AND `user_deleted_since_gmt` < :user_deleted_since_gmt';
        $Sth = $Db->PDO()->prepare($sql);
        unset($sql);

        $DateTimeUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $DateTimeUtc->sub(new \DateInterval('P' . $keepSoftDeleteForDays . 'D'));

        $Sth->bindValue(':user_deleted_since_gmt', $DateTimeUtc->format('Y-m-d H:i:s'));
        $Sth->execute();

        $result = $Sth->fetchAll();
        if (is_array($result)) {
            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($Container);
            foreach ($result as $row) {
                $UsersDb->delete((int) $row->user_id);
            }// endforeach;
            unset($row, $UsersDb);
        }
        unset($result);

        $Sth->closeCursor();
        unset($DateTimeUtc, $keepSoftDeleteForDays, $Sth);
    }// execute


}
