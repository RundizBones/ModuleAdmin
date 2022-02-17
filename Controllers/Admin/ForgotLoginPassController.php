<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Forgot login, password controller.
 * 
 * @since 0.1
 */
class ForgotLoginPassController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use Users\Traits\UsersTrait;


    /**
     * @var object|null The user row that get from users table. This property is for get and access across methods without get it again and again.
     */
    protected $userRow;


    /**
     * Generate reset password key if not exists or expired.
     * 
     * @param int $user_id The user ID.
     * @return array Return associative array with `key` and `time` in keys. The `time` key is date/time value that this key will be expired.
     */
    private function generateResetPasswordKey(int $user_id): array
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $resetPasswordKey = $UserFieldsDb->generateKeyWithWaitTime(
            $user_id,
            'rdbadmin_uf_resetpassword_key',
            'rdbadmin_uf_resetpassword_time',
            $ConfigDb->get('rdbadmin_UserConfirmWait', 10)
        );

        $output = [];

        $output['key'] = ($resetPasswordKey['readableKey'] ?? '');
        $output['time'] = ($resetPasswordKey['keyTime'] ?? '');

        if (isset($resetPasswordKey['regenerate']) && $resetPasswordKey['regenerate'] === true) {
            $UserFieldsDb->update($user_id, 'rdbadmin_uf_resetpassword_key', ($resetPasswordKey['encryptedKey'] ?? null), true);
            $UserFieldsDb->update($user_id, 'rdbadmin_uf_resetpassword_time', $output['time'], true);

            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/controllers/admin/forgotloginpasscontroller', 0, 'The reset password key was regenerated and updated.', [$output['key']]);
                unset($Logger);
            }
        }

        unset($ConfigDb, $resetPasswordKey, $UserFieldsDb);

        return $output;
    }// generateResetPasswordKey


    /**
     * Display forgot login & password page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // honeypot (antibot)
        $AntiBot = new \Rdb\Modules\RdbAdmin\Libraries\AntiBot();
        $output['honeypotName'] = $AntiBot->setAndGetHoneypotName();
        unset($AntiBot);

        $output['loginUrl'] = $Url->getAppBasedPath() . '/admin/login' . $Url->getQuerystring();
        $output['forgotLoginPassUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['forgotLoginPassMethod'] = 'POST';
        $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath() . '/admin');
        if (stripos($output['gobackUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['gobackUrl'] = $Url->getAppBasedPath() . '/admin';
        } else {
            $output['gobackUrl'] = strip_tags($output['gobackUrl']);
        }

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $rdbAdminAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaLoginLogout'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaForgotLoginPass'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaForgotLoginPass', 
                'RdbaForgotLP', 
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'forgotLoginPassUrl' => $output['forgotLoginPassUrl'],
                    'forgotLoginPassMethod' => $output['forgotLoginPassMethod'],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            $output['pageTitle'] = __('Forgot username or password?');
            $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle']);
            $output['pageHtmlClasses'] = $this->getPageHtmlClasses(['rdba-login-logout-pages']);
            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/ForgotLoginPass/index_v', $output);

            unset($Assets, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// indexAction


    /**
     * Check if user is using proxy.
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
     * Display new password form fields page.
     * 
     * @return string
     */
    public function resetAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $tokenValue = $this->Input->request('token', null);
        if ($tokenValue !== null) {
            // if there is token.
            @list($user_id, $userEnteredResetPasswordKey) = explode('::', base64_decode($tokenValue));
            $user_id = (int) $user_id;

            if (empty($user_id) || empty($userEnteredResetPasswordKey)) {
                // if not found user_id, reset password key.
                $output['hideForm'] = true;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
                http_response_code(403);
            } else {
                $validateResult = $this->validateResetPasswordKey($user_id, $userEnteredResetPasswordKey);
                if ($validateResult !== true) {
                    // if validate reset key failed.
                    $output['hideForm'] = true;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
                    http_response_code(403);
                } else {
                    // if validate reset key passed.
                    // get user data.
                    $row = $this->userRow;
                    $output['user_login'] = $row->user_login;
                    $output['user_email'] = $row->user_email;
                    $output['user_display_name'] = $row->user_display_name;
                    unset($row);
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

            unset($user_id, $userEnteredResetPasswordKey);
        } else {
            // if there is no token.
            $output['hideForm'] = true;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
            http_response_code(403);
        }
        unset($tokenValue);

        $output['loginUrl'] = $Url->getAppBasedPath() . '/admin/login';
        $output['loginMethod'] = 'POST';
        $output['forgotLoginPassResetUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['forgotLoginPassResetMethod'] = 'POST';
        $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath() . '/admin');
        if (stripos($output['gobackUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['gobackUrl'] = $Url->getAppBasedPath() . '/admin';
        } else {
            $output['gobackUrl'] = strip_tags($output['gobackUrl']);
        }

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaLoginLogout'], $ModuleAssets->getModuleAssets());
            $Assets->addMultipleAssets('js', ['rdbaForgotLoginPassReset'], $ModuleAssets->getModuleAssets());
            $Assets->addJsObject(
                'rdbaForgotLoginPassReset', 
                'RdbaForgotLPR', 
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'loginMethod' => $output['loginMethod'],
                    'forgotLoginPassUrl' => $output['forgotLoginPassResetUrl'],
                    'forgotLoginPassMethod' => $output['forgotLoginPassResetMethod'],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['pageTitle'] = __('Reset your password');
            $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle']);
            $output['pageHtmlClasses'] = $this->getPageHtmlClasses(['rdba-login-logout-pages']);
            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/ForgotLoginPass/reset_v', $output);

            unset($Assets, $ModuleAssets, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// resetAction


    /**
     * Submit reset password request.
     * 
     * Verify email and then generate secret key and its expire send to email.<br>
     * User have to enter the link in that email to proceed reset password in next step.
     * 
     * @return string
     */
    public function submitRequestAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
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
            // prepare data for checking.
            $data = [];
            $data['user_email'] = trim($this->Input->post('user_email'));

            if (empty($data['user_email'])) {
                // if form validation failed.
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter your email.');
                $output['formFieldsValidation'] = [
                    'user_email' => 'required',
                ];
                http_response_code(400);
            } else {
                // if form validation passed.
                $data['antibot'] = trim($this->Input->post(\Rdb\Modules\RdbAdmin\Libraries\AntiBot::staticGetHoneypotName()));
                $checkAntibot = empty($data['antibot']);
                unset($data['antibot']);

                if ($checkAntibot === true) {
                    $formValidated = true;
                } else {
                    $formValidated = false;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('You have entered incorrect data.');// just showing incorrect.
                    http_response_code(400);
                }
                unset($checkAntibot);
            }

            if (isset($formValidated) && $formValidated === true) {
                /*
                 * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\ForgotLoginPassController->submitRequestAction.beforeCheckUser
                 * PluginHookDescription: Hook after form validated, before check user exists.
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
                $Plugins = $this->Container->get('Plugins');
                $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.beforeCheckUser', [$data, &$output, &$formValidated]);
                if (!is_array($output)) {
                    $output = $originalOutput;
                }
                if (!is_bool($formValidated)) {
                    $formValidated = $originalFormValidated;
                }
                unset($originalFormValidated, $originalOutput, $Plugins);
            }

            if (isset($formValidated) && $formValidated === true) {
                // if all form validation passed.
                $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                    $this->Container, 
                    [
                        'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/ForgotLoginPassController',
                    ]
                ))->getCacheObject();
                $cacheKey = 'forgotLPEmailSent_' . hash('sha512', $data['user_email']);

                if ($Cache->has($cacheKey)) {
                    // if email was sent recently.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('The email has just been sent recently, please wait and try again later.');
                    http_response_code(429);
                } else {
                    // if email did not sent recently.
                    $where = [];
                    $where['user_email'] = $data['user_email'];
                    $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
                    $result = $UsersDb->get($where);
                    unset($where);

                    if (empty($result)) {
                        // if user was not found.
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('The email was not found.');
                        http_response_code(404);
                    } else {
                        // if user was found.
                        if ($result->user_status != '1') {
                            // if user is disabled.
                            $output['formResultStatus'] = 'error';
                            $output['formResultMessage'] = sprintf(__('Your account has been disabled. (%1$s)'), __($result->user_statustext));
                            http_response_code(403);
                        } else {
                            // if user is enabled.
                            if ($this->isUserProxy() === true) {
                                // if user is using proxy.
                                // let them wait longer.
                                sleep(3);
                            }

                            // generate key and time expire if not existst.
                            $generateResult = $this->generateResetPasswordKey((int) $result->user_id);
                            $generatedKey = $generateResult['key'];
                            $generatedExpiry = $generateResult['time'];
                            unset($generateResult);

                            // init email class and get mailer.
                            $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);

                            try {
                                $tokenValue = base64_encode($result->user_id . '::' . $generatedKey);

                                // get mailer object.
                                $Mail = $Email->getMailer();
                                $Mail->addAddress($data['user_email'], $result->user_display_name);
                                $Mail->isHTML(true);

                                $Mail->Subject = __('You had requested to reset your password.');
                                $Url = new \Rdb\System\Libraries\Url($this->Container);
                                $replaces = [];
                                $replaces['%resetpasswordlink%'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/forgot-login-password/reset?token=' . rawurlencode($tokenValue);
                                $replaces['%tokenvalue%'] = $tokenValue;
                                $replaces['%expiredatetime%'] = $generatedExpiry;
                                $emailMessage = $Email->getMessage('RdbAdmin', 'ForgotLoginPass', $replaces);
                                unset($replaces, $Url);
                                $Mail->msgHtml($emailMessage, $Email->baseFolder);
                                $Mail->AltBody = $Mail->html2text($emailMessage);
                                unset($emailMessage);

                                if (defined('APP_ENV') && APP_ENV === 'development') {
                                    $output['debug_baseFolder'] = $Email->baseFolder;
                                }

                                $sendResult = $Mail->send();
                                if ($sendResult === true) {
                                    $Cache->set($cacheKey, true, 120);
                                    $output['formResultStatus'] = 'success';
                                    $output['formResultMessage'] = __('An email has been sent, please check your email and follow instruction.');
                                    $output['forgotLoginPasswordStep1'] = 'success';
                                } else {
                                    $output['formResultStatus'] = 'error';
                                    $output['formResultMessage'] = __('An email could not be sent.');
                                    http_response_code(502);
                                }
                                unset($sendResult, $Mail);
                            } catch (\Exception $e) {
                                $output['formResultStatus'] = 'error';
                                $output['formResultMessage'] = __('An email could not be sent.') . ' ' . $e->getMessage();
                                http_response_code(500);
                            }

                            unset($Email, $generatedExpiry, $generatedKey, $tokenValue);
                        }
                    }

                    unset($UsersDb);
                }
                unset($Cache, $cacheKey);
            }// endif; $formValidated
            unset($data, $formValidated);
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
    }// submitRequestAction


    /**
     * Submit new password.
     * 
     * @return string
     */
    public function submitResetAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
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
            @list($user_id, $userEnteredResetPasswordKey) = explode('::', base64_decode($tokenValue));
            $validateResult = $this->validateResetPasswordKey($user_id, $userEnteredResetPasswordKey);

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
                        $updateStatus = $UsersDb->update($dataUpdate, ['user_id' => $user_id, 'user_status' => 1, 'user_deleted' => 0]);
                    }
                    unset($dataUpdate);

                    if ($updateStatus !== true) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('Unable to update your new password, please try again.');
                        http_response_code(500);
                    } else {
                        // cleanup resetpassword key and its timeout.
                        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                        $UserFieldsDb->update($user_id, 'rdbadmin_uf_resetpassword_key', null);
                        $UserFieldsDb->update($user_id, 'rdbadmin_uf_resetpassword_time', null);
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
                                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/ForgotLoginPassController',
                            ]
                        ))->getCacheObject();
                        $cacheKey = 'forgotLPEmailSent_' . hash('sha512', $output['user_email']);
                        $Cache->delete($cacheKey);
                        unset($Cache, $cacheKey);

                        // success.
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('Success, you can now login using your new password.');
                        $output['forgotLoginPasswordStep2'] = 'success';
                    }

                    unset($UsersDb);
                }

                unset($data);
            } else {
                // if token is invalid.
                $output['hideForm'] = true;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
                http_response_code(403);
            }

            unset($tokenValue, $userEnteredResetPasswordKey, $user_id, $validateResult);
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
    }// submitResetAction


    /**
     * Validate reset password key. Also validate that user is really exists in DB.
     * 
     * @param int $user_id The user ID.
     * @param string $userEnteredResetPasswordKey Original or readable reset password key.
     * @return bool Return `true` if success, `false` for otherwise.
     */
    private function validateResetPasswordKey(int $user_id, string $userEnteredResetPasswordKey): bool
    {
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $resetPasswordKey = $UserFieldsDb->get($user_id, 'rdbadmin_uf_resetpassword_key');
        $resetPasswordTime = $UserFieldsDb->get($user_id, 'rdbadmin_uf_resetpassword_time');
        unset($UserFieldsDb);

        if (
            !isset($resetPasswordKey->field_value) || 
            !isset($resetPasswordTime->field_value) ||
            empty($resetPasswordKey) ||
            empty($resetPasswordTime) ||
            (isset($resetPasswordKey->field_value) && empty($resetPasswordKey->field_value))
        ) {
            // not found or found but it is empty.
            return false;
        }

        $realResetPasswordKey = $this->decryptUserFieldsKey($resetPasswordKey->field_value);

        if ($userEnteredResetPasswordKey !== $realResetPasswordKey) {
            // if key does not matched.
            unset($realResetPasswordKey, $resetPasswordKey, $resetPasswordTime);
            return false;
        }
        unset($realResetPasswordKey, $resetPasswordKey);

        $NowDt = new \DateTime();
        $ResetDt = new \DateTime($resetPasswordTime->field_value);
        if ($NowDt > $ResetDt) {
            // if current time is over reset timeout.
            unset($NowDt, $ResetDt, $resetPasswordTime);
            return false;
        }
        unset($NowDt, $ResetDt, $resetPasswordTime);

        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $result = $UsersDb->get(['user_id' => $user_id, 'user_status' => 1]);
        unset($UsersDb);
        if (empty($result) || $result === false) {
            // if user is not enabled or deleted
            return false;
        } else {
            $this->userRow = $result;
        }
        unset($result);

        return true;
    }// validateResetPasswordKey


}
