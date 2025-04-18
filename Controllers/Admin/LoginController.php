<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Login page controller.
 * 
 * @since 0.1
 */
class LoginController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use Users\Sessions\Traits\SessionsTrait;


    use Users\Traits\UsersTrait;


    /**
     * @var object|null The user row that get from users table. This property is for get and access across methods without get it again and again.
     */
    protected $userRow;


    /**
     * Do login process.
     * 
     * This method was called from `doLoginAction()` method.<br>
     * There is `http_response_code()` function call from here.<br>
     * If login success, it will write cookies here (including skip "antibot" cookie if applicable).
     * 
     * @param array $data The form data.
     * @param array $output The output views data.
     * @return array Return processed with output data that be able to merge with previous `$output` data in the action method.
     */
    protected function doLogin(array $data, array $output): array
    {
        if (isset($data['user_login_or_email'])) {
            $userLoginEmail = $data['user_login_or_email'];
        } elseif (isset($data['user_login'])) {
            $userLoginEmail = $data['user_login'];
        } else {
            $userLoginEmail = $data['user_email'];
        }

        $BruteForceLoginPrevention = new \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\BruteForceLoginPrevention($this->Container, $output['configDb']);
        $bruteForceResult = $BruteForceLoginPrevention->checkBruteForceStatus($userLoginEmail);

        if (isset($bruteForceResult['status']) && $bruteForceResult['status'] === 'authenticate') {
            // if brute-force prevention status is no problem (status = authenticate).
            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
            $UserLoginsDb = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);
            // check (username or email) and password.
            $doLoginResult = $UsersDb->checkLogin($data);
            $LoginSubController = new \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\LoginSubController($this->Container);

            if (isset($doLoginResult['result']) && $doLoginResult['result'] === true) {
                // if check login success. ----------------------------------------------------------------------------------
                // check 2 step verification.
                $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                $login2Step = $UserFieldsDb->get((int) $doLoginResult['user_id'], 'rdbadmin_uf_login2stepverification');

                if (isset($login2Step->field_value) && $login2Step->field_value === 'email') {
                    // if user setting use 2 step verification (by email).
                    // generate 2 step verification key and send to user's email.
                    $output = array_merge($output, $LoginSubController->send2faCodeEmail($data, $output, $doLoginResult, $UsersDb, $UserFieldsDb));
                    if (isset($output['emailSent']) && $output['emailSent'] === false) {
                        // if email was failed to sent.
                        unset($output['emailSent']);
                        $proceedLoginSuccess = true;
                    } else {
                        $Url = new \Rdb\System\Libraries\Url($this->Container);
                        $output['redirectUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/login/2fa';
                        if (isset($_POST['gobackUrl'])) {
                            $output['redirectUrl'] .= '?gobackUrl=' . rawurldecode($_POST['gobackUrl']);
                        }
                        unset($Url);
                    }
                    unset($output['emailSent']);
                } else {
                    // if user setting did not use 2 step verification.
                    // handle login success (including set cookie and record logins).
                    $proceedLoginSuccess = true;
                }

                if (isset($proceedLoginSuccess) && $proceedLoginSuccess === true) {
                    $output = array_merge(
                        $output, 
                        $LoginSubController->handleLoginSuccess($data, $output, $doLoginResult, $UserLoginsDb, $BruteForceLoginPrevention)
                    );
                }

                unset($UserFieldsDb);
                // endif; login success. ------------------------------------------------------------------------------
            } else {
                // if check login failed. (`$doLoginResult['result'] === false`).
                $failOutput = $LoginSubController->handleLoginFail($data, $output, $doLoginResult, $UsersDb, $UserLoginsDb, $BruteForceLoginPrevention);
                if (is_array($failOutput)) {
                    $output = array_merge($output, $failOutput);
                }
                unset($failOutput);
            }// endif; $doLoginResult['result']

            unset($doLoginResult, $LoginSubController, $UserLoginsDb, $UsersDb);
        } else {
            // if brute-force prevention status had a problem.
            $output['formResultStatus'] = 'error';
            if (isset($bruteForceResult['errorMessage'])) {
                $output['formResultMessage'] = $bruteForceResult['errorMessage'];
            }

            if (isset($bruteForceResult['statusCode']) && is_int($bruteForceResult['statusCode'])) {
                http_response_code($bruteForceResult['statusCode']);
            } else {
                http_response_code(403);
            }
        }// endif; $bruteForceResult['status']

        if (defined('APP_ENV') && APP_ENV === 'development') {
            $output['bruteForceResult'] = $bruteForceResult;
        }

        unset($BruteForceLoginPrevention, $bruteForceResult, $userLoginEmail);

        return $output;
    }// doLogin


    /**
     * Rest API do login.
     * 
     * @return string
     */
    public function doLoginAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $output = [];
        $output = array_merge($output, $this->getConfig());
        list ($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $data['user_login_or_email'] = trim($this->Input->post('user_login_or_email'));
            $data['user_login'] = trim($this->Input->post('user_login'));// accept for validate login with username only.
            $data['user_email'] = trim($this->Input->post('user_email'));// accept for validate login with email only.
            $data['user_password'] = $this->Input->post('user_password');
            $data['remember'] = $this->Input->post('remember');

            if (
                (
                    empty($data['user_login_or_email']) &&
                    empty($data['user_login']) &&
                    empty($data['user_email'])
                ) ||
                empty($data['user_password'])
            ) {
                // if form validation failed.
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter username and password.');
                $output['formFieldsValidation'] = [
                    'user_login_or_email|user_login|user_email' => 'required',
                    'user_password' => 'required',
                ];
                http_response_code(400);
            } else {
                // if form validation passed.
                // remove fields those are not visible in form fields.
                if ($data['user_login_or_email'] === '') {
                    unset($data['user_login_or_email']);
                }
                if ($data['user_login'] === '') {
                    unset($data['user_login']);
                }
                if ($data['user_email'] === '') {
                    unset($data['user_email']);
                }

                if ($this->isUserProxy() === true) {
                    // if user is using proxy.
                    // let them wait longer.
                    sleep(3);
                }

                $antibot = $this->Input->post(\Rdb\Modules\RdbAdmin\Libraries\AntiBot::staticGetHoneypotName());
                if (!empty($antibot)) {
                    $formValidated = false;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('You have entered incorrect data.');// just showing incorrect.
                    http_response_code(400);
                } else {
                    $formValidated = true;
                }
                unset($antibot);

                if (isset($formValidated) && $formValidated === true) {
                    /*
                     * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\LoginController->doLoginAction.beforeDoLogin
                     * PluginHookDescription: Hook on login page, after form validated, before do login.
                     * PluginHookParam: <br>
                     *      array $data The form input data.<br>
                     *      array $output This argument will be pass by reference, you can alter but variable type must be array. <br>
                     *              The output of alert messages that will be send to browser. <br>
                     *              The format is `array(array('formResultStatus' => 'error', 'formResultMessage' => 'My error message.'), array('formResultStatus' => 'success', 'formResultMessage' => 'Success message.'))`.<br>
                     *      bool $formValidated The status of form validated. This argument will be pass by reference. Set to `true` if passed, `false` if failed.
                     * PluginHookSince: 1.2.0
                     */
                    $originalOutput = $output;
                    $originalFormValidated = $formValidated;
                    /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                    $Plugins = $this->Container->get('Plugins');
                    $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.beforeDoLogin', [$data, &$output, &$formValidated]);
                    if (!is_array($output)) {
                        $output = $originalOutput;
                    }
                    if (!is_bool($formValidated)) {
                        $formValidated = $originalFormValidated;
                    }
                    unset($originalFormValidated, $originalOutput, $Plugins);
                }

                // do login process
                if (isset($formValidated) && $formValidated === true) {
                    $output = array_merge($output, $this->doLogin($data, $output));
                }
                unset($formValidated);
            }// endif; form validation

            unset($data);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        // remove sensitive info.
        $output = $this->removeSensitiveCfgInfo($output);

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doLoginAction


    /**
     * REST API do login with reset password action (login one time after logged out because simultaneous login).
     * 
     * @return string
     */
    public function doLoginResetAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            $tokenValue = $this->Input->request('token', null);
            @list($user_id, $userEnteredLoginResetKey) = explode('::', base64_decode($tokenValue));
            unset($tokenValue);

            $user_id = (int) $user_id;
            $validateResult = $this->validateLoginResetKey($user_id, $userEnteredLoginResetKey);

            if ($validateResult === true) {
                // if validated token and passed.
                // prepare data for checking.
                $data = [];
                $data['new_password'] = trim($this->Input->post('new_password'));

                if (empty($data['new_password'])) {
                    // if new password is not entered.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Please enter your new password.');
                    $output['formFieldsValidation'] = [
                        'new_password' => 'required',
                        'confirm_new_password' => 'required',
                    ];
                    http_response_code(400);
                } elseif ($data['new_password'] !== trim($this->Input->post('confirm_new_password'))) {
                    // if new password and its confirm does not matched.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Your new password and confirm does not matched.');
                    $output['formFieldsValidation'] = [
                        'confirm_new_password' => 'notmatch',
                    ];
                    http_response_code(400);
                } else {
                    // if form validation passed.
                    $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
                    $dataUpdate = [];
                    $dataUpdate['user_password'] = $UsersDb->hashPassword($data['new_password']);
                    $dataUpdate['user_status'] = 1;
                    $dataUpdate['user_statustext'] = null;
                    if ($dataUpdate['user_password'] === false) {
                        // if failed to hash password.
                        $updateStatus = false;
                        if ($this->Container->has('Logger')) {
                            /* @var $Logger \Rdb\System\Libraries\Logger */
                            $Logger = $this->Container->get('Logger');
                            $Logger->write('modules/rdbadmin/controllers/admin/forgotloginpasscontroller', 5, 'Password hash error.');
                            unset($Logger);
                        }
                    } else {
                        $updateStatus = $UsersDb->update($dataUpdate, ['user_id' => $user_id, 'user_status' => 0, 'user_deleted' => 0]);
                    }
                    unset($dataUpdate);

                    if ($updateStatus !== true) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('Unable to update your new password, please try again.');
                        http_response_code(500);
                    } else {
                        // cleanup login reset time key and its timeout.
                        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                        $UserFieldsDb->delete($user_id, 'rdbadmin_uf_simultaneouslogin_reset_key');
                        $UserFieldsDb->delete($user_id, 'rdbadmin_uf_simultaneouslogin_reset_time');
                        unset($UserFieldsDb);

                        $row = $UsersDb->get(['user_id' => $user_id]);
                        $output['user_login'] = $row->user_login;
                        $output['user_email'] = $row->user_email;
                        $output['user_display_name'] = $row->user_display_name;
                        unset($row);

                        // cleanup cache that store just sent email.
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
                        $Cache->delete($cacheKey);
                        unset($Cache, $cacheKey);

                        // success.
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('Success, you can now login using your new password.');
                        $output['changedPassword'] = true;
                    }

                    unset($UsersDb);
                }

                unset($data);
            } else {
                // if failed to validate token.
                $output['hideForm'] = true;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
                http_response_code(403);
            }// endif validate key result.

            unset($user_id, $userEnteredLoginResetKey, $validateResult);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// doLoginResetAction


    /**
     * REST API do 2 step verification.
     * 
     * This method will be redirect to login page if failed over x times and did not requested via ajax, rest api.
     * 
     * @return string
     */
    public function doMfaAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $output = [];
        $output = array_merge($output, $this->getConfig());

        list ($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            $user_id = (int) ($_SESSION['user_id'] ?? 0);

            $Url = new \Rdb\System\Libraries\Url($this->Container);
            $LoginSubController = new \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\LoginSubController($this->Container);
            $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);

            $output = array_merge(
                $output,
                $LoginSubController->doLogin2faVerify($user_id, $output, $UserFieldsDb, $Url)
            );

            // clear sensitive data.
            $output = $this->removeSensitiveCfgInfo($output);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// doMfaAction


    /**
     * Get common use configuration between methods.
     * 
     * This method was called from `doLoginAction()`, `indexAction()`, `resetAction()` methods.
     * 
     * @return array
     */
    protected function getConfig(): array
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configNames = [
            'rdbadmin_SiteName',
            'rdbadmin_SiteFavicon',
            'rdbadmin_UserRegister',
            'rdbadmin_UserLoginBruteforcePreventByIp',
            'rdbadmin_UserLoginBruteforcePreventByDc',
            'rdbadmin_UserLoginMaxFail',
            'rdbadmin_UserLoginMaxFailWait',
            'rdbadmin_UserLoginNotRememberLength',
            'rdbadmin_UserLoginRememberLength',
        ];
        $configDefaults = [
            '',
            '',
            '0',// rdbadmin_UserRegister
            '1',
            '1',
            '10',// rdbadmin_UserLoginMaxFail
            '60',
            '0',
            '20',// rdbadmin_UserLoginRememberLength
        ];

        $output = [];
        $output['configDb'] = $ConfigDb->get($configNames, $configDefaults);
        unset($ConfigDb, $configDefaults, $configNames);

        return $output;
    }// getConfig


    /**
     * Login page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output = array_merge($output, $this->getConfig(), $Csrf->createToken());
        unset($Csrf);

        // honeypot (antibot)
        $AntiBot = new \Rdb\Modules\RdbAdmin\Libraries\AntiBot();
        $output['honeypotName'] = $AntiBot->setAndGetHoneypotName();
        unset($AntiBot);

        $output['loginUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['loginMethod'] = 'POST';
        $output['forgotLoginPassUrl'] = $Url->getAppBasedPath(true) . '/admin/forgot-login-password' . $Url->getQuerystring();
        $output['registerUrl'] = $Url->getAppBasedPath(true) . '/admin/register' . $Url->getQuerystring();
        $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath(true) . '/admin');
        if (stripos($output['gobackUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['gobackUrl'] = $Url->getAppBasedPath(true) . '/admin';
        } else {
            $output['gobackUrl'] = strip_tags($output['gobackUrl']);
        }
        $output['pageTitle'] = __('Login');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses(['rdba-login-logout-pages', 'rdba-pagehtml-login']);

        // set required config if not exists.
        if (!isset($output['configDb']['rdbadmin_UserRegister'])) {
            $output['configDb']['rdbadmin_UserRegister'] = '0';
        }

        // display page alert message if exists.
        if (isset($_SESSION['loginPageAlertMessage'])) {
            $pageAlertMessage = json_decode($_SESSION['loginPageAlertMessage'], true);
            $output['pageAlertStatus'] = ($pageAlertMessage['pageAlertStatus'] ?? 'warning');
            $output['pageAlertMessage'] = strip_tags($pageAlertMessage['pageAlertMessage'] ?? '');
            if (isset($pageAlertMessage['pageAlertHttpStatus']) && is_int($pageAlertMessage['pageAlertHttpStatus'])) {
                http_response_code($pageAlertMessage['pageAlertHttpStatus']);
            }
            // also accept page alert dismissable
            if (isset($pageAlertMessage['pageAlertDismissable']) && is_bool($pageAlertMessage['pageAlertDismissable'])) {
                $output['pageAlertDismissable'] = $pageAlertMessage['pageAlertDismissable'];
            }
            unset($_SESSION['loginPageAlertMessage'], $pageAlertMessage);
        }

        // store special config value into variable before remove.
        $rdbadmin_UserRegister = ($output['configDb']['rdbadmin_UserRegister'] ?? '0');
        // remove sensitive info.
        $output = $this->removeSensitiveCfgInfo($output);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $MyModuleAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaLoginLogout'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdbaLogin'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaLogin', 
                'RdbaLogin', 
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'loginMethod' => $output['loginMethod'],
                    'forgotLoginPassUrl' => $output['forgotLoginPassUrl'],
                    'registerUrl' => $output['registerUrl'],
                    'configDb' => ['rdbadmin_UserRegister' => $rdbadmin_UserRegister],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['pageContent'] = $this->Views->render('Admin/Login/index_v', $output);

            unset($Assets, $MyModuleAssets, $rdbadmin_UserRegister, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// indexAction


    /**
     * Check if user is using proxy.
     * 
     * This method was called from `doLoginAction()` method.
     * 
     * @link https://stackoverflow.com/a/9251201/128761 Reference.
     * @link https://www.ipqualityscore.com/articles/view/1/how-to-detect-proxies-with-php Reference.
     * @return bool Return `true` if yes, `false` for no.
     */
    protected function isUserProxy(): bool
    {
        $proxyHeaders = array(
            'HTTP_VIA',
            'VIA',
            'Proxy-Connection',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED_FOR_IP',
            'X-PROXY-ID',
            'MT-PROXY-ID',
            'X-TINYPROXY',
            'X_FORWARDED_FOR',
            'FORWARDED_FOR',
            'X_FORWARDED',
            'FORWARDED',
            'CLIENT-IP',
            'CLIENT_IP',
            'PROXY-AGENT',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'FORWARDED_FOR_IP',
            'HTTP_PROXY_CONNECTION'
        );

        foreach ($proxyHeaders as $header) {
            if (isset($_SERVER[$header])) {
                return true;
            }
        }// endforeach;
        unset($header, $proxyHeaders);

        return false;
    }// isUserProxy


    /**
     * Display 2 step verification page.
     * 
     * @return string
     */
    public function mfaAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        if (!isset($_SESSION['user_id'])) {
            // if no login success before.
            $output = [];
            $output['redirect'] = $Url->getAppBasedPath(true) . '/admin/login';
            if (!$this->Input->isNonHtmlAccept() && !$this->Input->isXhr()) {
                $this->responseNoCache();
                header('Location: ' . $output['redirect'], true, 302);
                exit();
            } else {
                return $this->responseAcceptType($output);
            }
        }

        $output = [];
        $output = array_merge($output, $this->getConfig(), $Csrf->createToken());

        $output['loginUrl'] = $Url->getAppBasedPath(true) . '/admin/login';
        $output['loginMethod'] = 'POST';
        $output['loginMfaUrl'] = $Url->getAppBasedPath(true) . '/admin/login/2fa';
        $output['loginMfaMethod'] = 'POST';
        $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath(true) . '/admin');
        if (stripos($output['gobackUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['gobackUrl'] = $Url->getAppBasedPath(true) . '/admin';
        } else {
            $output['gobackUrl'] = strip_tags($output['gobackUrl']);
        }
        $output['pageTitle'] = __('Login');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses(['rdba-login-logout-pages']);
        // remove sensitive info
        $output = $this->removeSensitiveCfgInfo($output);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $MyModuleAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaLoginLogout'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdbaLoginMfa'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaLoginMfa', 
                'RdbaLoginMfa', 
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'loginMethod' => $output['loginMethod'],
                    'loginMfaUrl' => $output['loginMfaUrl'],
                    'loginMfaMethod' => $output['loginMfaMethod'],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            $output['Assets'] = $Assets;
            $output['pageContent'] = $this->Views->render('Admin/Login/mfa_v', $output);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// mfaAction


    /**
     * Remove sensitive config info that contains non-site configuration.
     * 
     * @since 1.2.5
     * @param array $output The output array that contain `configDb` array key.
     * @return array Return removed sensitive info.
     */
    private function removeSensitiveCfgInfo(array $output)
    {
        if (isset($output['configDb']) && is_array($output['configDb'])) {
            foreach ($output['configDb'] as $cfgKey => $cfgValue) {
                if (stripos($cfgKey, 'rdbadmin_Site') === false) {
                    // if non site config.
                    // remove it.
                    unset($output['configDb'][$cfgKey]);
                }
            }// endforeach;
            unset($cfgKey, $cfgValue);
        }

        return $output;
    }// removeSensitiveCfgInfo


    /**
     * Login one time after account locked due to user's setting about simultaneous login to log all out.
     * 
     * This will showing reset password form.
     * 
     * @return string
     */
    public function resetAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output = array_merge($output, $this->getConfig(), $Csrf->createToken());

        $tokenValue = $this->Input->request('token', null);
        if ($tokenValue !== null) {
            // if there is token.
            @list($user_id, $userEnteredLoginResetKey) = explode('::', base64_decode($tokenValue));
            $user_id = (int) $user_id;

            if (empty($user_id) || empty($userEnteredLoginResetKey)) {
                // if not found user_id, reset password key.
                $output['hideForm'] = true;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
                http_response_code(403);
            } else {
                $validateResult = $this->validateLoginResetKey($user_id, $userEnteredLoginResetKey);
                if ($validateResult !== true) {
                    // if validate login reset key failed.
                    $output['hideForm'] = true;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
                    http_response_code(403);
                } else {
                    // if validate login reset key passed.
                    // get user data.
                    $row = $this->userRow;
                    $output['user_login'] = $row->user_login;
                    $output['user_email'] = $row->user_email;
                    $output['user_display_name'] = $row->user_display_name;
                    unset($row);
                    $this->userRow = null;
                    // check 2 step auth.
                    $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                    $login2StepVerification = $UserFieldsDb->get($user_id, 'rdbadmin_uf_login2stepverification');
                    if (isset($login2StepVerification->field_value) && !empty($login2StepVerification->field_value)) {
                        $output['loginPageUrl'] = $Url->getAppBasedPath(true) . '/admin/login';
                        $output['login2StepVerification'] = true;
                        $output['login2StepVerificationMethod'] = $login2StepVerification->field_value;
                    }
                    unset($login2StepVerification, $UserFieldsDb);
                }
                unset($validateResult);
            }

            unset($user_id, $userEnteredLoginResetKey);
        } else {
            // if there is no token.
            $output['hideForm'] = true;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
            http_response_code(403);
        }
        unset($Csrf, $tokenValue);

        $output['loginUrl'] = $Url->getAppBasedPath(true) . '/admin/login';
        $output['loginMethod'] = 'POST';
        $output['loginResetUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['loginResetMethod'] = 'POST';
        $output['gobackUrl'] = $Url->getAppBasedPath(true) . '/admin';
        $output['pageTitle'] = __('Login recovery');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses(['rdba-login-logout-pages']);

        // remove sensitive info.
        $output = $this->removeSensitiveCfgInfo($output);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $MyModuleAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaLoginLogout'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdbaLoginReset'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaLoginReset', 
                'RdbaLoginReset', 
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'loginMethod' => $output['loginMethod'],
                    'loginResetUrl' => $output['loginResetUrl'],
                    'loginResetMethod' => $output['loginResetMethod'],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['pageContent'] = $this->Views->render('Admin/Login/reset_v', $output);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// resetAction


    /**
     * Validate login reset key.
     * 
     * This method was called from `doLoginResetAction()`, `resetAction()` methods.
     * 
     * @param int $user_id The user ID.
     * @param string $userEnteredLoginResetKey The login reset key that user entered (readable one).
     * @return bool Return `true` if success, `false` for failure.
     */
    protected function validateLoginResetKey(int $user_id, string $userEnteredLoginResetKey): bool
    {
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $loginResetKey = $UserFieldsDb->get($user_id, 'rdbadmin_uf_simultaneouslogin_reset_key');
        $loginResetTime = $UserFieldsDb->get($user_id, 'rdbadmin_uf_simultaneouslogin_reset_time');
        unset($UserFieldsDb);

        if (
            !isset($loginResetKey->field_value) || 
            !isset($loginResetTime->field_value) ||
            empty($loginResetKey) ||
            empty($loginResetTime) ||
            (isset($loginResetKey->field_value) && empty($loginResetKey->field_value))
        ) {
            // not found or found but it is empty.
            return false;
        }

        $realLoginResetKey = $this->decryptUserFieldsKey($loginResetKey->field_value);

        if ($userEnteredLoginResetKey !== $realLoginResetKey) {
            // if key does not matched.
            unset($loginResetKey, $loginResetTime, $realLoginResetKey);
            return false;
        }
        unset($realLoginResetKey, $loginResetKey);

        $NowDt = new \DateTime();
        $ResetDt = new \DateTime($loginResetTime->field_value);

        if ($NowDt > $ResetDt) {
            // if current time is over reset timeout.
            unset($NowDt, $ResetDt, $loginResetTime);
            return false;
        }
        unset($NowDt, $ResetDt, $loginResetTime);

        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $result = $UsersDb->get(['user_id' => $user_id, 'user_status' => 0]);
        unset($UsersDb);
        if (empty($result) || $result === false) {
            // if user is not disabled or deleted
            return false;
        } else {
            $this->userRow = $result;
        }
        unset($result);

        return true;
    }// validateLoginResetKey


}
