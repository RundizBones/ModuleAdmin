<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\_SubControllers;


/**
 * Description of Login
 *
 * @author mr.v
 */
class LoginSubController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Sessions\Traits\SessionsTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    /**
     * Clear 2 step verification code, time, temp data, cache, session (user_id).
     * 
     * @param int $user_id The user ID.
     * @param \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb UserFieldsDb model class.
     */
    protected function doLogin2faClearData(
        int $user_id,
        \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb
    )
    {
        $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container, 
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/LoginController',
            ]
        ))->getCacheObject();
        // The cache key below will be set and delete only in this controller.
        $cacheKey = '2faSubmitRetries_' . $user_id;
        $Cache->delete($cacheKey);

        $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container, 
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/LoginController',
            ]
        ))->getCacheObject();
        // The cache key below will be set and delete only in this controller.
        $cacheKey = '2faEmailCodeSent_' . $user_id;
        $Cache->delete($cacheKey);

        unset($_SESSION['user_id']);

        $UserFieldsDb->delete($user_id, 'rdbadmin_uf_login2stepverification_key');
        $UserFieldsDb->delete($user_id, 'rdbadmin_uf_login2stepverification_time');
        $UserFieldsDb->delete($user_id, 'rdbadmin_uf_login2stepverification_tmpdata');
    }// doLogin2faClearData


    /**
     * Do verify 2 step verification login.
     * 
     * If success:<br>
     *     Clear code, time, temp data, session (user_id).<br>
     *     Call to `handleLoginSuccess()` method.<br>
     *     If not request via ajax or rest api.<br>
     *         Redirect to new url.<br>
     *     Else.<br>
     *         Return redirect url and login status result.<br>
     * If fail:<br>
     *     If not over 3 times.<br>
     *         It will be show http response code, error message, error form status.<br>
     *     If over 3 times.<br>
     *         If this request has not done via rest api or ajax then it will be redirect to new url if failed over x times.<br>
     *         Clear code, time, temp data, session (user_id).
     * 
     * @param int $user_id
     * @param array $output
     * @param \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb
     * @param \Rdb\System\Libraries\Url $Url
     * @return array Return associative array with keys:<br>
     *                          'gobackUrl' (string - optional) Go back URL on success only.<br>
     *                          'redirectUrl' (string - optional) Redirect URL.<br>
     *                          'formResultStatus' (string - optional) Alert message status.<br>
     *                          'formResultMessage' (string, array) Alert messages.<br>
     *                          'submitTimes' (int) Number of submit failed.
     */
    public function doLogin2faVerify(
        int $user_id,
        array $output,
        \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb,
        \Rdb\System\Libraries\Url $Url
    ): array
    {
        $mfaKey = $UserFieldsDb->get($user_id, 'rdbadmin_uf_login2stepverification_key');
        $mfaTime = $UserFieldsDb->get($user_id, 'rdbadmin_uf_login2stepverification_time');
        $loginTempDataObject = $UserFieldsDb->get($user_id, 'rdbadmin_uf_login2stepverification_tmpdata');

        $DateTime = new \DateTime();
        $MfaTime = new \DateTime(($mfaTime->field_value ?? ''));
        unset($mfaTime);

        if (
            isset($mfaKey->field_value) && 
            !empty($mfaKey->field_value) &&
            $this->decryptUserFieldsKey($mfaKey->field_value) === $this->Input->post('login2stepverification_key') &&// match user entered code.
            $DateTime <= $MfaTime &&// code not expired.
            isset($loginTempDataObject->field_value) &&
            !empty($loginTempDataObject->field_value)// there are temp data.
        ) {
            // if correct code.
            $loginTempData = $loginTempDataObject->field_value;
            $data = ($loginTempData['data'] ?? []);
            $doLoginResult = ($loginTempData['doLoginResult'] ?? []);

            $BruteForceLoginPrevention = new \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention($this->Container);
            $UserLoginsDb = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);

            $output = array_merge(
                $output, 
                $this->handleLoginSuccess($data, $output, $doLoginResult, $UserLoginsDb, $BruteForceLoginPrevention)
            );

            $this->doLogin2faClearData($user_id, $UserFieldsDb);

            unset($BruteForceLoginPrevention, $data, $doLoginResult, $loginTempData, $UserLoginsDb);

            $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath(true) . '/admin');
            if (stripos($output['gobackUrl'], '//') !== false) {
                // if found double slash, this means it can go to other domain.
                // do not allow this, change the login URL.
                $output['gobackUrl'] = $Url->getAppBasedPath(true) . '/admin';
            } else {
                $output['gobackUrl'] = strip_tags($output['gobackUrl']);
            }

            if (!$this->Input->isNonHtmlAccept() && !$this->Input->isXhr()) {
                $this->responseNoCache();
                header('Location: ' . $output['gobackUrl'], true, 302);
                exit();
            }
        } else {
            // if incorrect code.
            $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                $this->Container, 
                [
                    'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/LoginController',
                ]
            ))->getCacheObject();
            // The cache key below will be set and delete only in this controller.
            $cacheKey = '2faSubmitRetries_' . $user_id;

            if ($Cache->has($cacheKey) && $Cache->get($cacheKey, 0) > 3) {
                // if more than 3 tries.
                // clear 2 step verification code datas.
                $this->doLogin2faClearData($user_id, $UserFieldsDb);

                // redirect to login page.
                $output['redirectUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/login';
                if (isset($_GET['gobackUrl'])) {
                    $output['redirectUrl'] .= '?gobackUrl=' . rawurldecode($_GET['gobackUrl']);
                }

                if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
                    http_response_code(403);
                } else {
                    $this->responseNoCache();
                    header('Location: ' . $output['redirectUrl'], true, 302);
                    exit();
                }
            } else {
                if ($Cache->has($cacheKey)) {
                    $submitTime = (intval($Cache->get($cacheKey, 0)) + 1);
                } else {
                    $submitTime = 1;
                }
                $Cache->set($cacheKey, $submitTime, (10*60));// minutes*seconds.

                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to verify code or your code may expired.') . ' ' . __('If your code are expired, please try to login again.');
                $output['submitTimes'] = $submitTime;
                if ($submitTime > 3) {
                    $messageAlert = [];
                    $messageAlert[] = $output['formResultMessage'];
                    $messageAlert[] = sprintf(__('You had submitted more than %d times, please try to login again.'), 3);
                    $output['formResultMessage'] = $messageAlert;
                    unset($messageAlert);
                }
                http_response_code(400);
            }
        }

        unset($DateTime, $loginTempDataObject, $mfaKey, $MfaTime);

        return $output;
    }// doLogin2faVerify


    /**
     *  On check login failed, record logins (failed) if configuration was disabled brute-force prevention.
     * 
     * if configuration was enabled brute-force prevention via dc, it will be already record there in `BruteForceLoginPrevention->registerFailedAuth()`.<br>
     * This method was called from `handleLoginFail()` method.
     * 
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param array $output The output array that contain `configDb` in key.
     * @param string $untranslatedMessage The error message that was not translated.
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb UserLoginsDb model class.
     */
    protected function doLoginFailedRecordLogins(
        array $doLoginResult, 
        array $output, 
        string $untranslatedMessage,
        \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb
    )
    {
        if (
            isset($output['configDb']['rdbadmin_UserLoginBruteforcePreventByDc']) &&
            $output['configDb']['rdbadmin_UserLoginBruteforcePreventByDc'] === '0'
        ) {
            // if configuration was disabled brute-force prevention. otherwise don't record because it will be double record.
            // if configuration was enabled brute-force prevention via dc, it will be already record there in `BruteForceLoginPrevention->registerFailedAuth()`.
            // record logins (failed).
            $recData = [];
            $recData['user_id'] = $doLoginResult['user_id'];
            $recData['userlogin_result'] = 0;
            $recData['userlogin_result_text'] = $untranslatedMessage;
            $UserLoginsDb->recordLogins($recData);
            unset($recData);
        }
    }// doLoginFailedRecordLogins


    /**
     * On check login failed, if brute-force attack prevention is enabled then it will be register the failed authentication.
     * 
     * This method was called from `handleLoginFail()` method.
     * 
     * @see \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention::registerFailedAuth()
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param array $regFailedData The register data. For more information please read in `BruteForceLoginPrevention->registerFailedAuth()` method.
     * @param \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention $BruteForceLoginPrevention BruteForceLoginPrevention class.
     */
    protected function doLoginFailedRegisterBruteForceFailedAuth(
        array $doLoginResult, 
        array $regFailedData, 
        \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention $BruteForceLoginPrevention
    )
    {
        if (isset($doLoginResult['userStatusText'])) {
            $Serializer = new \Rundiz\Serializer\Serializer();
            $regFailedData['userlogin_result_text_data'] = $Serializer->maybeSerialize([$doLoginResult['userStatusText']]);
            unset($Serializer);
        }

        // `registerFailedAuth()` will be working only if prevention is enabled at least one method.
        $BruteForceLoginPrevention->registerFailedAuth($regFailedData);
    }// doLoginFailedRegisterBruteForceFailedAuth


    /**
     * On login failed, if uset status is disabled and contain word about simultaneous login locked or contain key in user_fields table then re-send the login with reset password link.
     * 
     * This will not check if password is correct or not (in case that users forgot their password so, the forgot password link will not working in this case).<br>
     * This method will set or add error message(s) and also set http response code (example: 4xx).<br>
     * This method was called from `handleLoginFail()` method.
     * 
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param array $output The associative output used in `doLogin()` method. This method will modify the output.
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb UserLoginsDb model class.
     */
    protected function doLoginFailedSendLoginResetEmail(
        array $doLoginResult,
        array &$output,
        \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb
    )
    {
        // check if user account was locked AND it is because of simultaneous login setting.
        // (user fields contain 'rdbadmin_uf_simultaneouslogin_reset_key').
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $userFieldRow = $UserFieldsDb->get($doLoginResult['user_id'], 'rdbadmin_uf_simultaneouslogin_reset_key');

        if (
            array_key_exists('userStatus', $doLoginResult) &&
            $doLoginResult['userStatus'] == '0' &&
            (
                (// have these words.
                    isset($doLoginResult['userStatusText']) &&
                    (
                        stripos($doLoginResult['userStatusText'], 'simultaneous') !== false ||
                        stripos($doLoginResult['userStatusText'], 'locked') !== false ||
                        (
                            stripos($doLoginResult['userStatusText'], 'login') !== false &&
                            stripos($doLoginResult['userStatusText'], 'email') !== false
                        )
                    )
                ) ||
                (// user fields contain reset value and not empty.
                    isset($userFieldRow->field_value) && 
                    !empty($userFieldRow->field_value)
                )
            )
        ) {
            // if user account locked and because simultaneous login.
            // send login email again.
            // set cache to limit send max 10 minutes per time.
            $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                $this->Container, 
                [
                    'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/LoginController',
                ]
            ))->getCacheObject();
            $user_id = (int) $doLoginResult['user_id'];
            // This cache is set, check, delete in 3 files: Controllers/Admin/LoginController.php, 
            // Controllers/Admin/Users/Sessions/Traits/SessionsTrait.php, 
            // Controllers/_SubControllers/LoginSubController.php
            $cacheKey = 'simultaneousLoginResetEmailSent_' . hash('sha512', $user_id);

            if ($Cache->has($cacheKey)) {
                // if email was sent recently.
                $output['formResultStatus'] = 'error';
                $messageArray = [];
                if (isset($output['formResultMessage'])) {
                    $messageArray[] = $output['formResultMessage'];
                }
                $messageArray[] = __('The email has just been sent recently, please wait and try again later.');
                $output['formResultMessage'] = $messageArray;
                unset($messageArray);
                http_response_code(429);
            } else {
                // if email did not sent recently.
                $this->sessionTraitLogoutAll($user_id, $UserLoginsDb, $UserFieldsDb);
            }

            unset($Cache, $cacheKey, $user_id);
        }

        unset($userFieldRow, $UserFieldsDb);
    }// doLoginFailedSendLoginResetEmail


    /**
     * On check login failed, set the error message, set http response code (example: 4xx).
     * 
     * This method was called from `handleLoginFail()` method.
     * 
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param \Rdb\Modules\RdbAdmin\Models\UsersDb $UsersDb UsersDb model class.
     * @return array Return associative array with keys:
     *                          `formResultMessage` (string) The error message that was translated.<br>
     *                          `untranslatedMessage` (string) The error message that was not translated.
     */
    protected function doLoginFailedSetErrorMessage(array $doLoginResult, \Rdb\Modules\RdbAdmin\Models\UsersDb $UsersDb): array
    {
        $output = [];

        if (is_array($doLoginResult) && array_key_exists('errorCode', $doLoginResult)) {
            switch ($doLoginResult['errorCode']) {
                case $UsersDb::LOGIN_ERR_ACCOUNT_DISABLED;
                    if (!isset($doLoginResult['userStatusText'])) {
                        $doLoginResult['userStatusText'] = '';
                    }
                    $untranslatedMessage = noop__('Your account has been disabled. (%1$s)');
                    $output['formResultMessage'] = sprintf(__($untranslatedMessage), __($doLoginResult['userStatusText']));
                    http_response_code(403);
                    break;
                case $UsersDb::LOGIN_ERR_USERPASSWORD_INCORRECT;
                default:
                    $untranslatedMessage = noop__('The username or password is incorrect.');
                    $output['formResultMessage'] = __($untranslatedMessage);
                    http_response_code(401);
                    break;
            }
        } else {
            $untranslatedMessage = noop__('The username or password is incorrect.');
            $output['formResultMessage'] = __($untranslatedMessage);
            http_response_code(401);
        }

        $output['untranslatedMessage'] = $untranslatedMessage;
        unset($untranslatedMessage);

        return $output;
    }// doLoginFailedSetErrorMessage


    /**
     * On check login success, record logins.
     * 
     * This method was called from `handleLoginSuccess()` method.
     * 
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param string|null $deviceCookieSignature Device cookie signature got from `$BruteForceLoginPrevention->deviceCookieSignature` property.
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb UserLoginsDb model class.
     * @param array $recordLoginsData Additional record logins data.
     */
    protected function doLoginSucceessRecordLogins(
        array $doLoginResult,
        $deviceCookieSignature,
        \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb,
        array $recordLoginsData = []
    )
    {
        $recData = [];
        $recData['user_id'] = $doLoginResult['user_id'];
        $recData['userlogin_result'] = 1;
        $recData['userlogin_session_key'] = $doLoginResult['user']['sessionKey'];
        if (is_string($deviceCookieSignature) && !empty($deviceCookieSignature)) {
            $recData['userlogin_dc_sign'] = $deviceCookieSignature;
        }

        if (!empty($recordLoginsData)) {
            $recData = array_merge($recData, $recordLoginsData);
            $recordLoginsData = [];
        }

        $UserLoginsDb->recordLogins($recData);

        unset($recData);
    }// doLoginSucceessRecordLogins


    /**
     * On check login success, set logged in cookie.
     * 
     * This method was called from `handleLoginSuccess()` method.
     * 
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param int $cookieExpires The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch. In other words, you'll most likely set this with the time() function plus the number of seconds before you want it to expire.
     */
    public function doLoginSuccessSetCookie(array $doLoginResult, int $cookieExpires)
    {
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $Cookie->setEncryption('rdbaLoggedinKey');
        $Cookie->set('rdbadmin_cookie_users', $doLoginResult['user'], $cookieExpires, '/');
        unset($Cookie);
    }// doLoginSuccessSetCookie


    /**
     * On check login success, update last login date/time.
     * 
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     */
    protected function doLoginSuccessUpdateLastLogin(array $doLoginResult)
    {
        $dataUpdate = [];
        $dataUpdate['user_lastlogin'] = date('Y-m-d H:i:s');
        $dataUpdate['user_lastlogin_gmt'] = gmdate('Y-m-d H:i:s');

        // update using Db class not UsersDb model because we do not need to update last update column.
        if ($this->Container->has('Db')) {
            /* @var $Db \Rdb\System\Libraries\Db */
            $Db = $this->Container->get('Db');
        } else {
            $Db = new \Rdb\System\Libraries\Db($this->Container);
        }

        $Db->update($Db->tableName('users'), $dataUpdate, ['user_id' => $doLoginResult['user_id']]);

        unset($dataUpdate, $Db);
    }// doLoginSuccessUpdateLastLogin


    /**
     * Get cookie expires.
     * 
     * This method was called from `handleLoginSuccess()` method.
     * 
     * @param array $data The form data.
     * @param array $output The output views data. Require array that contain keys:<br>
     *                                      `['configDb']['rdbadmin_UserLoginRememberLength']`,<br>
     *                                      `['configDb']['rdbadmin_UserLoginNotRememberLength']`
     * @return array Return associative array with keys:
     *                          `expireDay` (int) expires in day,<br>
     *                          `expireTimestamp` (int) expires in timestamp but it can be 0 (for session expires).
     */
    protected function getCookieExpires(array $data, array $output): array
    {
        if (array_key_exists('remember', $data) && $data['remember'] == '1') {
            $cookieExpires = (int) ($output['configDb']['rdbadmin_UserLoginRememberLength'] ?? 20);// get only number of days.
            $cookieExpiresConfig = $cookieExpires;
        } else {
            $cookieExpires = (int) ($output['configDb']['rdbadmin_UserLoginNotRememberLength'] ?? 0);// get only number of days.
            $cookieExpiresConfig = $cookieExpires;
        }

        if ($cookieExpiresConfig != 0) {// use !=
            // if cookie expires config is not zero (not session expire).
            $cookieExpires = intval(abs(time() + ($cookieExpires * 24 * 60 * 60)));// add days to timestamp.
        }

        $output = [];
        $output['expireDay'] = $cookieExpiresConfig;
        $output['expireTimestamp'] = $cookieExpires;

        unset($cookieExpires, $cookieExpiresConfig);

        return $output;
    }// getCookieExpires


    /**
     * Handle login failed.
     * 
     * Set error message, record failed logins, register brute-force failed auth.<br>
     * In case that account was locked because simultaneous login setting then re-send email.
     * 
     * @param array $data
     * @param array $output
     * @param array $doLoginResult
     * @param \Rdb\Modules\RdbAdmin\Models\UsersDb $UsersDb
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb
     * @param \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention $BruteForceLoginPrevention
     * @return array
     */
    public function handleLoginFail(
        array $data,
        array $output,
        array $doLoginResult,
        \Rdb\Modules\RdbAdmin\Models\UsersDb $UsersDb,
        \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb,
        \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention $BruteForceLoginPrevention
    ): array
    {
        if (isset($data['user_login_or_email'])) {
            $userLoginEmail = $data['user_login_or_email'];
        } elseif (isset($data['user_login'])) {
            $userLoginEmail = $data['user_login'];
        } else {
            $userLoginEmail = $data['user_email'];
        }

        // set form result status.
        $output['formResultStatus'] = 'error';

        // set error message and http status code. --------------------------------------
        $output = array_merge($output, $this->doLoginFailedSetErrorMessage($doLoginResult, $UsersDb));
        $untranslatedMessage = ($output['untranslatedMessage'] ?? '');
        unset($output['untranslatedMessage']);
        // end set error message and http status code. ----------------------------------

        // prepare variable for register failed auth.
        $regFailedData = [];

        if (array_key_exists('user_id', $doLoginResult)) {
            // if found user but something wrong (such as wrong password). (contain user_id in result).
            $regFailedData['user_id'] = $doLoginResult['user_id'];
            // record failed login.
            $this->doLoginFailedRecordLogins($doLoginResult, $output, $untranslatedMessage, $UserLoginsDb);

            // check for locked account because simultaneous login and resend login email.
            $this->doLoginFailedSendLoginResetEmail($doLoginResult, $output, $UserLoginsDb);
        }// endif; check login result failed but contain user_id.

        // register failed auth to prevent brute-force attack. -----------------------------
        $regFailedData['user_login_email'] = $userLoginEmail;
        $regFailedData['userlogin_result_text'] = $untranslatedMessage;
        $this->doLoginFailedRegisterBruteForceFailedAuth($doLoginResult, $regFailedData, $BruteForceLoginPrevention);
        unset($regFailedData);
        // end register failed auth to prevent brute-force attack. -------------------------

        // show total failed count.
        if (isset($bruteForceResult['totalFailed'])) {
            $output['totalFailed'] = ($bruteForceResult['totalFailed'] + 1);
        }

        unset($untranslatedMessage, $userLoginEmail);

        return $output;
    }// handleLoginFail


    /**
     * Handle login success.
     * 
     * The processes in this method are:<br>
     * * set session key to `$doLoginResult['user']['sessionKey']` array.<br>
     * * update last login to users table.<br>
     * * set login cookie.<br>
     * * set new device cookie (for brute-force attack prevention).<br>
     * * delete brute-force locked-out.<br>
     * * record logins data (user agent, ip, session key, success status, etc).<br>
     * * set output success message and status.
     * 
     * @param array $data The form data.
     * @param array $output The output views data. Require array that contain keys:<br>
     *                                      `['configDb']['rdbadmin_UserLoginRememberLength']`,<br>
     *                                      `['configDb']['rdbadmin_UserLoginNotRememberLength']`
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb UserLoginsDb model class.
     * @param \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention $BruteForceLoginPrevention BruteForceLoginPrevention class.
     * @return array Return associative array with keys:<br>
     *                          'formResultStatus' (string) if there is alert message(s).<br>
     *                          'formResultMessage' (string) if there is alert message(s).<br>
     *                          'loggedIn' (bool) `true` if login success, `false` for otherwise.<br>
     *                          'loggedInData' (array) some user data.
     */
    public function handleLoginSuccess(
        array $data,
        array $output,
        array $doLoginResult,
        \Rdb\Modules\RdbAdmin\Models\UserLoginsDb $UserLoginsDb,
        \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention $BruteForceLoginPrevention
    ): array
    {
        if (isset($data['user_login_or_email'])) {
            $userLoginEmail = $data['user_login_or_email'];
        } elseif (isset($data['user_login'])) {
            $userLoginEmail = $data['user_login'];
        } else {
            $userLoginEmail = $data['user_email'];
        }

        $recordLoginsData = [];

        // generate user login session key.
        $sessionKey = $UserLoginsDb->generateSessionKey((int) $doLoginResult['user_id']);
        $doLoginResult['user']['sessionKey'] = $sessionKey;
        unset($sessionKey);

        // update last login to users table.
        $this->doLoginSuccessUpdateLastLogin($doLoginResult);

        // set cookie expires.
        $cookieExpireArray = $this->getCookieExpires($data, $output);
        $cookieExpiresConfig = ($cookieExpireArray['expireDay'] ?? 0);
        $cookieExpires = ($cookieExpireArray['expireTimestamp'] ?? 0);
        unset($cookieExpireArray);

        // set record logins expiry date/time data
        if ($cookieExpires > 0) {
            $recordLoginsData['userlogin_expire_date'] = date('Y-m-d H:i:s', $cookieExpires);
            $recordLoginsData['userlogin_expire_date_gmt'] = gmdate('Y-m-d H:i:s', $cookieExpires);
        } else {
            $DateTime = new \DateTime();
            $DateTime->add(new \DateInterval('P1D'));
            $recordLoginsData['userlogin_expire_date'] = $DateTime->format('Y-m-d H:i:s');
            $recordLoginsData['userlogin_expire_date_gmt'] = gmdate('Y-m-d H:i:s', $DateTime->getTimestamp());
            unset($DateTime);
        }
        $doLoginResult['user']['cookieExpireGMT'] = $recordLoginsData['userlogin_expire_date_gmt'];

        // start write cookies -------------------------------------------
        // write login cookie.
        $this->doLoginSuccessSetCookie($doLoginResult, $cookieExpires);

        // issue new device cookie to client (Device Cookie based).
        $BruteForceLoginPrevention->issueNewDeviceCookie($userLoginEmail);

        unset($cookieExpires, $cookieExpiresConfig, $userLoginEmail);
        // end write cookies -----------------------------------------------

        // delete brute-force locked-out (IP based).
        $BruteForceLoginPrevention->deleteBruteForceIpStatus();

        // record logins (success).
        $this->doLoginSucceessRecordLogins($doLoginResult, $BruteForceLoginPrevention->deviceCookieSignature, $UserLoginsDb, $recordLoginsData);

        unset($recordLoginsData);

        // set output success message and status. ---------------------
        $output['formResultStatus'] = 'success';
        $output['formResultMessage'] = __('Login successfully.');
        $output['loggedIn'] = true;// really logged in. this is important.
        $output['loggedInData'] = $doLoginResult['user'];
        // end set output success message and status. -----------------

        return $output;
    }// handleLoginSuccess


    /**
     * Send 2 step verification code to email.
     * 
     * This method will be set http response code if contains error.<br>
     * If send success, this method will be write temp data to db and set user id to session to use it later.
     * 
     * @param array $data The form data.
     * @param array $output The output views data.
     * @param array $doLoginResult The check login result that have got from `\Rdb\Modules\RdbAdmin\Models\UsersDb->checkLogin()` method.
     * @param \Rdb\Modules\RdbAdmin\Models\UsersDb $UsersDb UsersDb model class.
     * @param \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb UserFieldsDb model class.
     * @return array Return associative array with the same `$output` as in argument. Additional keys are:<br>
     *                          'formResultStatus' (string) if contain alert message(s).<br>
     *                          'formResultMessage' (array) if contain alert message(s).<br>
     *                          'emailSent' (bool) if sent successfully it will be `true` otherwise will be `false`.<br>
     */
    public function send2faCodeEmail(
        array $data,
        array $output,
        array $doLoginResult,
        \Rdb\Modules\RdbAdmin\Models\UsersDb $UsersDb,
        \Rdb\Modules\RdbAdmin\Models\UserFieldsDb $UserFieldsDb
    ): array
    {
        $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container, 
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/LoginController',
            ]
        ))->getCacheObject();
        $user_id = (int) $doLoginResult['user_id'];
        // The cache key below will be set and delete only in this controller.
        $cacheKey = '2faEmailCodeSent_' . $user_id;

        if ($Cache->has($cacheKey)) {
            // if email was sent recently.
            $output['formResultStatus'] = 'error';
            $messageArray = [];
            if (isset($output['formResultMessage'])) {
                $messageArray[] = $output['formResultMessage'];
            }
            $messageArray[] = __('The 2 step verification code email has just been sent recently, please wait and try again later.');
            $output['formResultMessage'] = $messageArray;
            unset($messageArray);
            http_response_code(429);
        } else {
            // if email did not sent recently.
            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $mfaKey = $UserFieldsDb->generateKeyWithWaitTime(
                $user_id,
                'rdbadmin_uf_login2stepverification_key',
                'rdbadmin_uf_login2stepverification_time',
                $ConfigDb->get('rdbadmin_UserConfirmWait', 10),
                [
                    'keyLength' => 6,
                    'keyCharacters' => '0123456789',
                ]
            );
            unset($ConfigDb);

            if (isset($mfaKey['regenerate']) && $mfaKey['regenerate'] === true) {
                $UserFieldsDb->update($user_id, 'rdbadmin_uf_login2stepverification_key', ($mfaKey['encryptedKey'] ?? null), true);
                $UserFieldsDb->update($user_id, 'rdbadmin_uf_login2stepverification_time', ($mfaKey['keyTime'] ?? null), true);
            }

            $userRow = $UsersDb->get(['user_id' => $user_id]);

            // init email class and get mailer.
            $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);

            try {
                // get mailer object.
                $Mail = $Email->getMailer();
                $Mail->addAddress($userRow->user_email, $userRow->user_display_name);
                $Mail->isHTML(true);

                $Mail->Subject = __('2 Step verification code');
                $replaces = [];
                $replaces['%userdisplayname%'] = $userRow->user_display_name;
                $replaces['%mfacode%'] = ($mfaKey['readableKey'] ?? null);
                $replaces['%expiredatetime%'] = ($mfaKey['keyTime'] ?? null);
                $replaces['%useragent%'] = $this->Input->server('HTTP_USER_AGENT');
                $replaces['%ipaddress%'] = $this->Input->server('REMOTE_ADDR');
                $emailMessage = $Email->getMessage('RdbAdmin', 'Login2StepVerification', $replaces);
                unset($replaces);
                $Mail->msgHtml($emailMessage, $Email->baseFolder);
                $Mail->AltBody = $Mail->html2text($emailMessage);
                unset($emailMessage);

                $Mail->send();

                // set cache that email was sent recently.
                $Cache->set($cacheKey, true, 120);

                $output['emailSent'] = true;
                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = __('2 Step verification code was sent successfully, please enter the code you recieved.');

                $UserFieldsDb->update(
                    $user_id, 
                    'rdbadmin_uf_login2stepverification_tmpdata', 
                    [
                        'data' => $data,
                        'doLoginResult' => $doLoginResult,
                    ],
                    true
                );

                $_SESSION['user_id'] = $user_id;
            } catch (\Exception $e) {
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/_subcontrollers/loginsubcontroller', 4, 'An email could not be sent. ' . $e->getMessage());
                    unset($Logger);
                }

                $output['emailSent'] = false;

                $pageAlertMessage['pageAlertStatus'] = 'error';
                $pageAlertMessage['pageAlertMessage'] = __('A 2 step verification email could not be sent.');
                $_SESSION['pageAlertMessage'] = json_encode($pageAlertMessage);
                unset($pageAlertMessage);
            }
        }

        unset($Cache, $cacheKey, $Email, $mfaKey, $user_id, $userRow);
        return $output;
    }// send2faCodeEmail


}
