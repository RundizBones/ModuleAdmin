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
            WHERE `userlogin_result` = 1 
                AND `userlogin_expire_date` >= NOW()
                AND `userlogin_id` IN (SELECT MAX(`userlogin_id`) FROM `' . $userLoginsTable . '` GROUP BY `user_id`)
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
            [
                'name' => '<a href="https://github.com/RundizBones/framework" target="_blank">' . __('Framework') . '</a>',
                'value' => sprintf(__('Version %1$s'), $this->systemSummaryGetFrameworkVersion()),
            ],
        ];

        $return['content'] = $this->Views->render('Admin/UI/Widgets/systemSummary_v', $output);

        return $return;
    }// systemSummary


    /**
     * Get framework's version.
     * 
     * @since 1.1.1
     * @return string
     */
    protected function systemSummaryGetFrameworkVersion(): string
    {
        $output = '';

        if (
            defined('ROOT_PATH') &&
            is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'App.php')
        ) {
            $File = new \SplFileObject(ROOT_PATH . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'App.php');
            $fileContent = '';
            $i = 0;
            while (!$File->eof()) {
                $fileContent .= $File->fgets();
                $i++;
                if ($i >= 30) {
                    // grab only 30 max line is enough.
                    break;
                }
            }
            $File = null;
            unset($File, $i);
            // replace newlines to unix (\n) only.
            $fileContent = preg_replace('~\R~u', "\n", $fileContent);// https://stackoverflow.com/a/7836692/128761

            preg_match('#(?:\/\*(?:[^*]|(?:\*[^\/]))*\*\/)#iu', $fileContent, $firstDocblock);
            unset($fileContent);
            if (isset($firstDocblock[0])) {
                preg_match_all('#@([0-9a-z\-\_]+) *(.*)\n#iu', $firstDocblock[0], $matches, PREG_SET_ORDER);
                unset($firstDocblock);
                if (isset($matches) && is_array($matches)) {
                    foreach ($matches as $key => $item) {
                        if (isset($item[1]) && isset($item[2]) && strtolower($item[1]) === 'version') {
                            unset($matches);
                            $output = $item[2];
                            break;
                        }
                    }// endforeach;
                    unset($item, $key);
                }
                unset($matches);
            }
        }

        return $output;
    }// systemSummaryGetFrameworkVersion


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

        $sql = 'SELECT `user_id`, `user_deleted`, `user_status` FROM `' . $usersTable . '` WHERE `user_deleted` = 0';
        $Sth = $PDO->prepare($sql);
        unset($sql);
        $Sth->execute();
        $results = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($PDO, $Sth);

        $output['totalUsers'] = count($results);
        $totalEnabled = 0;
        $totalDisabled = 0;
        if (is_iterable($results)) {
            foreach ($results as $row) {
                if ($row->user_status === '1') {
                    ++$totalEnabled;
                } elseif ($row->user_status === '0') {
                    ++$totalDisabled;
                }
            }// endforeach;
            unset($row);
        }
        unset($results);

        $output['totalUsersEnabled'] = $totalEnabled;
        $output['totalUsersDisabled'] = $totalDisabled;
        unset($totalDisabled, $totalEnabled);

        $return['content'] = $this->Views->render('Admin/UI/Widgets/userSummary_v', $output);

        unset($output);

        return $return;
    }// userSummary


}
