<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits;


/**
 * Users trait.
 * 
 * @since 0.1
 */
trait UsersTrait
{


    /**
     * Form validation for add and update user.
     * 
     * Validate required and valid form fields.<br>
     * Validate that selected roles did not have higher priority that the user who add or update them.<br>
     * Validate username and email must not exists.<br>
     * This method was called from `doAddAction()` method.
     * 
     * @param array $data The associative array form data.
     * @param array $dataField Associative array for `user_fields` table.
     * @param array $dataUsersRoles Associative array for `users_roles` table.
     * @param string $saveType Type of saving data. It can be 'insert' or 'update'. Default is 'insert'.
     * @param string|int $user_id The `user_id` to check where `$saveType` is 'update' that any user else data must be unique.
     * @return array Return associative array with keys if contain at least one error:<br>
     *                          `formResultStatus` (if error),<br>
     *                          `formResultMessage` (if error) The result message,<br>
     *                          `formFieldsValidation` (optional) Fields that contain errors,<br>
     *                          `responseStatus` (optional) For HTTP response status,
     */
    protected function addUpdateUserFormValidation(array $data, array $dataField = [], array $dataUsersRoles = [], $saveType = 'insert', $user_id = ''): array
    {
        if ($saveType === 'update' && trim($user_id) === '') {
            trigger_error('Required attribute: $user_id.');
        }

        $errors = [];
        $output = [];
        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();

        if (isset($data['user_login']) && empty($data['user_login'])) {
            $errors['user_login']['message'] = __('Please enter username.');
            $errors['user_login']['fieldsValidation'] = 'required';
        } elseif (isset($data['user_login']) && !empty($data['user_login'])) {
            if ($RdbaString->sanitizeUsername($data['user_login']) !== $data['user_login']) {
                // if invalid username.
                $errors['user_login']['message'] = __('Please enter a valid username.');
                $errors['user_login']['fieldsValidation'] = 'invalid';
            }
        }

        if (isset($data['user_email']) && empty($data['user_email'])) {
            $errors['user_email']['message'] = __('Please enter email.');
            $errors['user_email']['fieldsValidation'] = 'required';
        }

        if (
            isset($data['user_email']) && 
            filter_var($data['user_email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            // if invalid email.
            $errors['user_email']['message'] = __('Please enter your correct email.');
            $errors['user_email']['fieldsValidation'] = 'invalid';
        }

        if (isset($data['user_password'])) {
            if (trim($data['user_password']) === '') {
                // if password is empty (not use empty() to check because it can be 0).
                $errors['user_password']['message'] = __('Please enter password.');
                $errors['user_password']['fieldsValidation'] = 'required';
            } elseif (
                !isset($data['confirm_password']) || 
                (isset($data['confirm_password']) && $data['user_password'] !== $data['confirm_password'])
            ) {
                // if password and confirm does not matched.
                $errors['confirm_password']['message'] = __('The password and confirmation is not matched.');;
                $errors['confirm_password']['fieldsValidation'] = 'notmatch';
            }

            if (isset($data['user_login']) && $RdbaString->sanitizeUsername($data['user_login']) === $data['user_password']) {
                // if username equals to password.
                $errors['user_password']['message'] = __('Username and password could not be same.');
                $errors['user_password']['fieldsValidation'] = 'invalid';
            }
        }

        unset($RdbaString);

        if (isset($dataUsersRoles['roleIds']) && !empty($dataUsersRoles['roleIds'])) {
            // if entered user roles.
            // validate new user's roles have no higher priority that current user who add them.
            $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);

            $currentUserId = 0;
            if ($this->Container->has('UsersSessionsTrait')) {
                $UsersSessionsTrait = $this->Container->get('UsersSessionsTrait');
                if (isset($UsersSessionsTrait->userSessionCookieData['user_id'])) {
                    $currentUserId = $UsersSessionsTrait->userSessionCookieData['user_id'];
                }
                unset($UsersSessionsTrait);
            }

            $options = [];
            $options['where']['user_id'] = $currentUserId;
            $options['limit'] = 1;
            $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
            $myRoles = $UsersRolesDb->listItems($options);
            unset($currentUserId, $options, $UsersRolesDb);

            if (isset($myRoles['items']) && !empty($myRoles['items'])) {
                $myRoles = array_shift($myRoles['items']);

                $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
                $options = [];
                $options['roleIdsIn'] = $dataUsersRoles['roleIds'];
                $options['unlimited'] = true;
                $listSelectedRoles = $UserRolesDb->listItems($options);
                unset($options, $UserRolesDb);

                if (isset($listSelectedRoles['items']) && is_array($listSelectedRoles['items'])) {
                    foreach ($listSelectedRoles['items'] as $row) {
                        if ($row->userrole_priority < $myRoles->userrole_priority) {
                            $errors['user_roles']['message'] = __('You cannot set new user\'s role that is higher that you.') . 
                                ' (' . __('%1$s has higher priority than %2$s', '<strong>' . $row->userrole_name . '</strong>', '<strong>' . $myRoles->userrole_name . '</strong>') . ')';
                            $errors['user_roles']['fieldsValidation'] = 'invalid';
                        }
                    }// endforeach;
                    unset($row);
                } else {
                    $errors['user_roles']['message'] = __('Unable to retrieve new user\'s role. Cannot save user, please contact administrator.');
                    $errors['user_roles']['fieldsValidation'] = 'invalid';
                }

                unset($listSelectedRoles);
            } else {
                $errors['user_roles']['message'] = __('Unable to retrieve your user role to check with new user\'s role. Cannot save user, please contact administrator.');
                $errors['user_roles']['fieldsValidation'] = 'invalid';
            }

            unset($myRoles);
        }

        if (count($errors) == 0) {
            // if found no errors.
            // verify username, email must not exists.
            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

            if ($saveType === 'insert') {
                // if check for insert.
                if (isset($data['user_login']) && !empty($UsersDb->get(['user_login' => $data['user_login']]))) {
                    $errors['user_login']['message'] = __('This username is already in use.');
                    $errors['user_login']['fieldsValidation'] = 'invalid';
                }

                if (isset($data['user_email']) && !empty($UsersDb->get(['user_email' => $data['user_email']]))) {
                    $errors['user_email']['message'] = __('This email is already in use.');
                    $errors['user_email']['fieldsValidation'] = 'invalid';
                }
            } elseif ($saveType === 'update') {
                // if check for update.
                if (isset($data['user_login'])) {
                    $options = [];
                    $options['where'] = [
                        'user_login' => $data['user_login'],
                        'users.user_id' => '!= ' . $user_id,
                    ];
                    $userData = $UsersDb->listItems($options);
                    unset($options);
                    if (!isset($userData['total']) || (isset($userData['total']) && $userData['total'] > 0)) {
                        $errors['user_login']['message'] = __('This username is already in use.');
                        $errors['user_login']['fieldsValidation'] = 'invalid';
                    }
                    unset($userData);
                }

                if (isset($data['user_email'])) {
                    $options = [];
                    $options['where'] = [
                        'user_email' => $data['user_email'],
                        'users.user_id' => '!= ' . $user_id,
                    ];
                    $userData = $UsersDb->listItems($options);
                    unset($options);
                    if (!isset($userData['total']) || (isset($userData['total']) && $userData['total'] > 0)) {
                        $errors['user_email']['message'] = __('This email is already in use.');
                        $errors['user_email']['fieldsValidation'] = 'invalid';
                    }
                    unset($userData);
                }
            }

            unset($UsersDb);
        }

        if (count($errors) >= 1) {
            // if found errors.
            // merge error messages.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = [];
            $output['formFieldsValidation'] = [];
            foreach ($errors as $key => $item) {
                $output['formResultMessage'][] = ($item['message'] ?? '');
                $output['formFieldsValidation'][$key] = ($item['fieldsValidation'] ?? 'invalid');
            }// endforeach;
            unset($item, $key);
            $output['responseStatus'] = 400;
        }

        unset($errors);
        return $output;
    }// addUpdateUserFormValidation


    /**
     * Decrypt user fields key.
     * 
     * @param string|object $encryptedKey The encrypted key string. Or you may set user_fields object from PDO query instead.
     * @return string Return decrypted key string or return empty string if failed to decrypted.
     */
    protected function decryptUserFieldsKey($encryptedKey): string
    {
        $output = '';

        if (is_object($encryptedKey) && isset($encryptedKey->field_value)) {
            $encryptedKey = (string) $encryptedKey->field_value;
        } elseif (is_scalar($encryptedKey)) {
            if (is_bool($encryptedKey)) {
                $encryptedKey = '';
            } else {
                $encryptedKey = (string) $encryptedKey;
            }
        } else {
            $encryptedKey = '';
        }

        /* @var $Config \Rdb\System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $Config->setModule('RdbAdmin');

        $Encryption = new \Rdb\Modules\RdbAdmin\Libraries\Encryption();
        $decrypted = $Encryption->decrypt($encryptedKey, $Config->get('rdbaUserFieldsKeys', 'hash'));
        if (!is_null($decrypted)) {
            $output = $decrypted;
        }
        unset($decrypted);

        unset($Config, $Encryption);

        return $output;
    }// decryptUserFieldsKey


    /**
     * Generate user fields key.
     * 
     * Generate keys that can be use in change email confirmation, register confirmation, etc.
     * 
     * @param int $length The length of key.
     * @return array Return associative array with 'readableKey', 'encryptedKey' keys.
     */
    protected function generateUserFieldsKey(int $length = 8): array
    {
        $output = [];

        /* @var $Config \Rdb\System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $Config->setModule('RdbAdmin');

        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
        $Encryption = new \Rdb\Modules\RdbAdmin\Libraries\Encryption();

        $output['readableKey'] = $RdbaString->random($length);
        $output['encryptedKey'] = $Encryption->encrypt($output['readableKey'], $Config->get('rdbaUserFieldsKeys', 'hash'));

        unset($Config, $Encryption, $RdbaString);

        return $output;
    }// generateUserFieldsKey


    /**
     * Get URLs and methods about user pages.
     * 
     * @param string $user_id The user ID.
     * @return array Return associative array.
     */
    protected function getUserUrlsMethods($user_id = ''): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['actionsUsersUrl'] = $urlAppBased . '/admin/users/actions';// bulk actions confirmation page.

        $output['addUserUrl'] = $urlAppBased . '/admin/users';// add user form submit via rest api.
        $output['addUserMethod'] = 'POST';

        $output['avatarUploadRESTUrl'] = $urlAppBased . '/admin/users/{{user_id}}/avatar';
        $output['avatarUploadRESTMethod'] = 'POST';
        $output['avatarDeleteRESTUrl'] = $urlAppBased . '/admin/users/{{user_id}}/avatar';
        $output['avatarDeleteRESTMethod'] = 'DELETE';

        if (is_numeric($user_id)) {
            $output['editUserUrl'] = $urlAppBased . '/admin/users/edit/' . $user_id;// edit user page with user_id.
            $output['editUserPreviousEmailsUrl'] = $urlAppBased . '/admin/users/' . $user_id . '/previous-emails';// previous emails page and rest api get data.
            $output['editUserPreviousEmailsMethod'] = 'GET';
        }
        $output['editUserPageUrlBase'] = $urlAppBased . '/admin/users/edit';// edit user page.
        $output['editUserSubmitUrlBase'] = $urlAppBased . '/admin/users';// edit user form submit via rest api.
        $output['editUserMethod'] = 'PATCH';
        $output['editUsersSubmitUrlBase'] = $urlAppBased . '/admin/users/actions';// edit multiple users form submit via rest api. this action come from bulk action page.
        $output['editUsersMethod'] = 'PATCH';

        $output['deleteMeUrl'] = $urlAppBased . '/admin/users/delete/me';// delete myself confirmation page.
        $output['deleteMeSubmitUrl'] = $urlAppBased . '/admin/users';// delete myself via rest api.
        $output['deleteMeMethod'] = 'DELETE';

        $output['deleteUsersUrlBase'] = $urlAppBased . '/admin/users';// delete users via rest api.
        $output['deleteUsersMethod'] = 'DELETE';

        $output['getUsersUrl'] = $urlAppBased . '/admin/users';// display users list page, also get users data via rest api.
        $output['getUsersMethod'] = 'GET';
        $output['getUserUrlBase'] = $urlAppBased . '/admin/users';// get a single user data via rest api.
        $output['getUserMethod'] = 'GET';// method for get a single user data via rest api.

        $output['viewLoginsUrl'] = $urlAppBased . '/admin/users/{{user_id}}/sessions';// display logins page, also get logins data via rest api.
        $output['viewLoginsMethod'] = 'GET';

        $output['deleteLoginsSubmitUrl'] = $urlAppBased . '/admin/users/{{user_id}}/sessions';// delete login sessions via rest api.
        $output['deleteLoginsMethod'] = 'DELETE';

        unset($Url, $urlAppBased);

        return $output;
    }// getUserUrlsMethods


    /**
     * Logout target user.
     * 
     * @param array $cookieData The associative array of cookie data. This value can get from `Cookie` class. The array keys are:<br>
     *                          `user_id` (required).<br>
     *                          `sessionKey` (optional) For delete specific session key from `user_logins` table.
     * @param bool $logoutAllDevice Set to `true` to logout all device, `false` for specific session key.
     */
    protected function logoutUser(array $cookieData = [], bool $logoutAllDevice = false)
    {
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $Cookie->setEncryption('rdbaLoggedinKey');

        if (empty($cookieData)) {
            $cookieData = $Cookie->get('rdbadmin_cookie_users');
        }

        // delete login data in db.
        $UserLogins = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);
        if ($logoutAllDevice === true) {
            // if logout on ALL devices.
            if (is_array($cookieData) && array_key_exists('user_id', $cookieData)) {
                $UserLogins->delete(['user_id' => $cookieData['user_id']]);
            }
        } else {
            // if logout on selected device only.
            if (is_array($cookieData) && array_key_exists('sessionKey', $cookieData) && array_key_exists('user_id', $cookieData)) {
                $UserLogins->delete(['user_id' => $cookieData['user_id'], 'userlogin_session_key' => $cookieData['sessionKey']]);
            }
        }
        unset($cookieData, $UserLogins);

        // remove logged in cookie
        $Cookie->set('rdbadmin_cookie_users', '', (time() - 99999), '/');
        
        // if there are any additional data that contain login session, destroy them here below this line.

        unset($Cookie);
    }// logoutUser


}
