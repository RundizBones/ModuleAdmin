<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Widgets;


/**
 * Admin dashboard (admin home) widgets controller.
 * 
 * @since 0.1
 */
class WidgetsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->Languages->getHelpers();
    }// __construct


    /**
     * Display last x logged in users.
     * 
     * @return array
     */
    public function lastLoggedinUsers(): array
    {
        $output = [];
        $output = array_merge($output, $this->getConfigDb());
        $return = [];

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $PDO = $this->Db->PDO();
        $userLoginsTable = $this->Db->tableName('user_logins');
        $usersTable = $this->Db->tableName('users');

        $output['limitUsers'] = 5;
        $sql = 'SELECT `user_logins`.`userlogin_id`, 
                `user_logins`.`user_id`, 
                `user_logins`.`userlogin_ua`, 
                `user_logins`.`userlogin_ip`, 
                `user_logins`.`userlogin_date`, 
                `user_logins`.`userlogin_date_gmt`, 
                `user_logins`.`userlogin_expire_date`,
                `user_logins`.`userlogin_expire_date_gmt`,
                `user_logins`.`userlogin_result`, 
                `users`.`user_id`, 
                `users`.`user_login`, 
                `users`.`user_email`, 
                `users`.`user_display_name`
            FROM `' . $userLoginsTable . '` AS `user_logins`
            INNER JOIN `' . $usersTable . '` AS `users` ON `user_logins`.`user_id` = `users`.`user_id`
            WHERE `userlogin_result` = 1 AND `userlogin_expire_date` >= NOW()
            GROUP BY `user_logins`.`user_id`
            ORDER BY `userlogin_id` DESC
            LIMIT 0, ' . $output['limitUsers'];
        $Sth = $PDO->prepare($sql);
        $Sth->execute();
        $output['result'] = $Sth->fetchAll();
        $Sth->closeCursor();

        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        $output['editPermission'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'edit');
        $output['editUserUrlBase'] = $Url->getAppBasedPath(true) . '/admin/users/edit';
        unset($UserPermissionsDb);

        include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

        $return['content'] = $this->Views->render('Admin/UI/Widgets/lastLoggedinUsers_v', $output);

        unset($output);

        return $return;
    }// lastLoggedinUsers


    /**
     * System summary widget.
     * 
     * @return array
     */
    public function systemSummary(): array
    {
        $output = [];
        $return = [];

        $PDO = $this->Db->PDO();
        $output['result'] = [
            [
                'name' => __('PHP'),
                'value' => PHP_VERSION . ' ' . (PHP_INT_SIZE === 4 ? 'x86' : 'x64'),
            ],
            [
                'name' => __('OS'),
                'value' => php_uname(),
            ],
            [
                'name' => __('DB'),
                'value' => $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME) . ' ' . $PDO->getAttribute(\PDO::ATTR_SERVER_VERSION),
            ],
        ];

        $return['content'] = $this->Views->render('Admin/UI/Widgets/systemSummary_v', $output);

        return $return;
    }// systemSummary


    /**
     * Display users summary widget.
     * 
     * @return array
     */
    public function userSummary(): array
    {
        $output = [];
        $return = [];

        $PDO = $this->Db->PDO();
        $usersTable = $this->Db->tableName('users');

        $sql = 'SELECT COUNT(*) FROM `' . $usersTable . '` WHERE `user_deleted` = 0';
        $Sth = $PDO->prepare($sql);
        $Sth->execute();
        $output['totalUsers'] = $Sth->fetchColumn();
        $Sth->closeCursor();

        $sql2 = $sql . ' AND `user_status` = 1';
        $Sth = $PDO->prepare($sql2);
        $Sth->execute();
        $output['totalUsersEnabled'] = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($sql2);

        $sql2 = $sql . ' AND `user_status` = 0';
        $Sth = $PDO->prepare($sql2);
        $Sth->execute();
        $output['totalUsersDisabled'] = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($sql2);

        unset($PDO, $sql, $Sth);

        $return['content'] = $this->Views->render('Admin/UI/Widgets/userSummary_v', $output);

        unset($output);

        return $return;
    }// userSummary


}