<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Sessions\Traits;


/**
 * User login sessions trait.
 * 
 * @since 0.1
 */
trait SessionsTrait
{


    /**
     * @var int The number of sessions that found this user logged in. This property is able to access after called to `isUserLoggedIn()` method.
     */
    protected $totalLoggedInSessions = 0;


    /**
     * @var array The cookie data of logged in user. This property is able to access after called to `isUserLoggedIn()` method.
     */
    protected $userSessionCookieData = [];


    /**
     * Check if user is logged in.
     * 
     * After called this method and it was `true` then you can access total sessions via `totalLoggedInSessions` property.<br>
     * After called this method and cookie is valid then you can access cookie data via `userSessionCookieData` property.<br>
     * If there is simultaneous login and user's setting is something that is not allowed (such as logout previous, logout all), it will be process here.
     * 
     * @param int $user_id The user ID. Set to `null` (default) to auto detect from cookie.
     * @param string $userlogin_session_key The logged in session key. Set to empty string (default) to auto detect from cookie.
     * @return bool Return `true` if logged in, `false` for not.
     */
    protected function isUserLoggedIn(int $user_id = null, string $userlogin_session_key = ''): bool
    {
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $Cookie->setEncryption('rdbaLoggedinKey');
        $cookieData = $Cookie->get('rdbadmin_cookie_users');// contain `user_id`, `user_display_name`, `sessionKey`.
        $this->userSessionCookieData = $cookieData;
        if (is_null($user_id)) {
            $user_id = (isset($cookieData['user_id']) ? (int) $cookieData['user_id'] : 0);
        }
        if (empty(trim($userlogin_session_key))) {
            $userlogin_session_key = ($cookieData['sessionKey'] ?? '');
        }
        unset($Cookie, $cookieData);

        $output = false;

        $UserLoginsDb = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);
        $isLoggedIn = $UserLoginsDb->isUserLoggedIn(
            $user_id, 
            ['userlogin_session_key' => $userlogin_session_key]
        );

        if ($isLoggedIn !== false) {
            // if user is logged in single session (true) or multiple sessions (int). NOT false.
            if (is_int($isLoggedIn) && $isLoggedIn >= 2) {
                // if logged in sessions is equal or more than 2.
                $this->totalLoggedInSessions = $isLoggedIn;
                // get simultaneous login setting for current user.
                $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                $simultaneousLogin = $UserFieldsDb->get($user_id, 'rdbadmin_uf_securitysimultaneouslogin');
                $simultaneousLogin = ($simultaneousLogin->field_value ?? 'allow');

                // check what to do with simultaneous login. refer values from Views/Admin/Users/edit_v.php.
                if ($simultaneousLogin === 'onlyLast') {
                    // if allow only last success login.
                    $this->sessionTraitLogoutPreviousSessions($user_id, $UserLoginsDb);
                } elseif ($simultaneousLogin === 'allOut') {
                    // if all login sessions out!
                    if ($this->Container != null && $this->Container->has('Logger')) {
                        /* @var $Logger \Rdb\System\Libraries\Logger */
                        $Logger = $this->Container->get('Logger');
                        $Logger->write('modules/rdbadmin/controllers/admin/users/sessions/traits/sessionstrait', 0, 'Found {totalSession} sessions.', ['totalSession' => $isLoggedIn]);
                        if (!is_null($UserLoginsDb->userLoginsResult)) {
                            $Logger->write('modules/rdbadmin/controllers/admin/users/sessions/traits/sessionstrait', 0, 'Get logins data for this user.', $UserLoginsDb->userLoginsResult);
                        }
                    }

                    $DateTime = new \DateTime();
                    if (is_array($UserLoginsDb->userLoginsResult)) {
                        foreach ($UserLoginsDb->userLoginsResult as $row) {
                            $ExpireDateTime = new \DateTime($row->userlogin_expire_date);
                            if ($ExpireDateTime >= $DateTime && $userlogin_session_key !== $row->userlogin_session_key) {
                                // if found that expire date is still not expired and it is not current session.
                                // write debug log.
                                if (isset($Logger)) {
                                    $Logger->write(
                                        'modules/rdbadmin/controllers/admin/users/sessions/traits/sessionstrait', 
                                        0, 
                                        'Login expire date/time: {expire}, current date/time: {current}, expire >= current: {expireOlder}', 
                                        [
                                            'expire' => $ExpireDateTime->format('Y-m-d H:i:s'), 
                                            'current' => $DateTime->format('Y-m-d H:i:s'),
                                            'expireOlder' => var_export($ExpireDateTime >= $DateTime, true),
                                        ]
                                    );
                                }
                                // logout all succeeded sessions, lock user account, and send emails to user to login.
                                $this->sessionTraitLogoutAll($user_id, $UserLoginsDb, $UserFieldsDb);
                                break;
                            }
                            unset($ExpireDateTime);
                        }// endforeach;
                        unset($row);
                    }
                    unset($DateTime, $Logger);
                } else {
                    // if allow simultaneous login.
                    // do nothing.
                }

                unset($UserFieldsDb);
                $output = true;// maybe remove this if worked with todo above.
            } else {
                $this->totalLoggedInSessions = 1;
                $output = true;
            }
        }
        unset($isLoggedIn, $UserLoginsDb);

        return $output;
    }// isUserLoggedIn


    /**
     * Logout all succeeded login sessions, lock user account, send login link to user's email.
     * 
     * This method also set cache that email was sent recently.<br>
     * This method was called from `isUserLoggedIn()`.<br>
     * This method was called from `LoginController->doLogin()`.
     * 
     * @param int $user_id
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb
     */
    protected function sessionTraitLogoutAll(
        int $user_id, 
        \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb, 
        \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb
    )
    {
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $userRow = $UsersDb->get(['user_id' => $user_id]);
        if (empty($userRow)) {
            unset($userRow, $UsersDb);
            return false;
        }

        // logout all succeeded sessions
        $sql = 'DELETE FROM `' . $UserLoginsDb->tableName . '` WHERE `user_id` = :user_id AND `userlogin_result` = 1';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->bindValue(':user_id', $user_id);
        $Sth->execute();
        $Sth->closeCursor();
        unset($sql, $Sth);

        /* @var $PDO \PDO */
        $PDO = $this->Db->PDO();
        $PDO->beginTransaction();

        try {
            // lock user account.
            if ($userRow->user_status != '0') {
                $UsersDb->update(
                    [
                        'user_status' => 0,
                        'user_statustext' => noop__('Your account has been locked due to your simultaneous login setting, please login using link that has been sent to your email.'),
                    ],
                    [
                        'user_id' => $user_id,
                    ]
                );
            }

            // send login link to email. ---------------------------------------------------------
            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $fieldKey = $UserFieldsDb->generateKeyWithWaitTime(
                $user_id, 
                'rdbadmin_uf_simultaneouslogin_reset_key', 
                'rdbadmin_uf_simultaneouslogin_reset_time', 
                $ConfigDb->get('rdbadmin_UserConfirmWait', 10)
            );
            $UserFieldsDb->update($user_id, 'rdbadmin_uf_simultaneouslogin_reset_key', ($fieldKey['encryptedKey'] ?? null), true);
            $UserFieldsDb->update($user_id, 'rdbadmin_uf_simultaneouslogin_reset_time', ($fieldKey['keyTime'] ?? null), true);
            unset($ConfigDb);

            // init email class and get mailer.
            $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
            $tokenValue = base64_encode($user_id . '::' . ($fieldKey['readableKey'] ?? null));

            // get mailer object.
            $Mail = $Email->getMailer();
            $Mail->addAddress($userRow->user_email, $userRow->user_display_name);
            $Mail->isHTML(true);

            $Mail->Subject = __('Your login link.');
            $Url = new \Rdb\System\Libraries\Url($this->Container);
            $replaces = [];
            $replaces['%loginresetlink%'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/login/reset?token=' . rawurlencode($tokenValue);
            $replaces['%loginlink%'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/login';
            $replaces['%tokenvalue%'] = $tokenValue;
            $replaces['%expiredatetime%'] = ($fieldKey['keyTime'] ?? null);
            $emailMessage = $Email->getMessage('RdbAdmin', 'LoginReset', $replaces);
            unset($replaces, $Url);
            $Mail->msgHtml($emailMessage, $Email->baseFolder);
            $Mail->AltBody = $Mail->html2text($emailMessage);
            unset($emailMessage);

            $sendMailStatus = $Mail->send();

            unset($Mail, $tokenValue);

            // set cache that email was sent recently.
            $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                $this->Container, 
                [
                    'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/LoginController',
                ]
            ))->getCacheObject();
            // This cache is set, check, delete in 3 files: Controllers/Admin/LoginController.php, 
            // Controllers/Admin/Users/Sessions/Traits/SessionsTrait.php, 
            // Controllers/_SubControllers/LoginSubController.php
            $cacheKey = 'simultaneousLoginResetEmailSent_' . hash('sha512', $user_id);
            $Cache->set($cacheKey, true, 120);
            unset($Cache, $cacheKey);

            $PDO->commit();
        } catch (\Exception $e) {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                if (isset($sendMailStatus) && $sendMailStatus !== true) {
                    $Logger->write(
                        'modules/rdbadmin/controllers/admin/users/sessions/traits/sessionstrait', 
                        4, 
                        'An email could not be sent. (' . $e->getMessage() . ')', 
                        [
                            'sendTo' => $userRow->user_email,
                            'trace' => $e->getTrace(),
                        ]
                    );
                } else {
                    $Logger->write(
                        'modules/rdbadmin/controllers/admin/users/sessions/traits/sessionstrait', 
                        4, 
                        'An error has been occur. (' . $e->getMessage() . ')', 
                        [
                            'trace' => $e->getTrace(),
                        ]
                    );
                }
                unset($Logger);
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $pageAlertMessage['pageAlertStatus'] = 'error';
            $pageAlertMessage['pageAlertMessage'] = __('Simultaneous login detected but unable to send login link to your email.');
            $_SESSION['loginPageAlertMessage'] = json_encode($pageAlertMessage);
            unset($pageAlertMessage);

            $PDO->rollBack();
        }

        unset($Email, $fieldKey, $sendMailStatus);
        // end send login link to email. ----------------------------------------------------

        unset($PDO, $userRow, $UsersDb);
    }// sessionTraitLogoutAll


    /**
     * Logout all sessions before latest succeeded login. (logout only succeeded login).
     * 
     * This method was called from `isUserLoggedIn()`.
     * 
     * @param int $user_id
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb
     */
    private function sessionTraitLogoutPreviousSessions(int $user_id, \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb)
    {
        // @link https://stackoverflow.com/questions/578867/sql-query-delete-all-records-from-the-table-except-latest-n Original source code.
        $sql = 'DELETE FROM `' . $UserLoginsDb->tableName . '` 
            WHERE `userlogin_id` IN (
                SELECT `userlogin_id` FROM (
                    SELECT * FROM `' . $UserLoginsDb->tableName . '` 
                        WHERE `user_id` = :user_id 
                            AND `userlogin_session_key` != :userlogin_session_key 
                            AND `userlogin_result` = 1
                ) notMySession
            )
                AND `user_id` = :user_id 
                AND `userlogin_result` = 1
            ';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->bindValue(':user_id', $user_id);
        $Sth->bindValue(':userlogin_session_key', $this->userSessionCookieData['sessionKey']);
        $Sth->execute();
        $Sth->closeCursor();
        unset($sql, $Sth);
    }// sessionTraitLogoutPreviousSessions


}
