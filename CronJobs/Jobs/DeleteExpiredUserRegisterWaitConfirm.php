<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\CronJobs\Jobs;


/**
 * Delete expired users that was added or register and wait for confirmation by the link in email.
 * 
 * @since 0.1
 */
class DeleteExpiredUserRegisterWaitConfirm
{


    /**
     * Execute the job.
     * 
     * @param \Rdb\System\Container $Container The DI Container class.
     * @param \Rdb\System\Libraries\Db $Db The Database class.
     */
    public static function execute(\Rdb\System\Container $Container, \Rdb\System\Libraries\Db $Db)
    {
        $keepWaitActivateDays = 2;// keep for xx days.
        $sql = 'SELECT * FROM `' . $Db->tableName('user_fields') . '` AS `user_fields`
            LEFT JOIN `' . $Db->tableName('users') . '` AS `users` ON `users`.`user_id` = `user_fields`.`user_id`
            WHERE `users`.`user_status` = 0 
                AND `users`.`user_deleted` = 0 
                AND `user_fields`.`field_name` = \'rdbadmin_uf_adduser_waitactivation_since\'
                AND `user_fields`.`field_value` < :rdbadmin_uf_adduser_waitactivation_since';
        $Sth = $Db->PDO()->prepare($sql);
        unset($sql);

        $DateTimeUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $DateTimeUtc->sub(new \DateInterval('P' . $keepWaitActivateDays . 'D'));

        $Sth->bindValue(':rdbadmin_uf_adduser_waitactivation_since', $DateTimeUtc->format('Y-m-d H:i:s'));
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
        unset($DateTimeUtc, $keepWaitActivateDays, $Sth);
    }// execute


}
