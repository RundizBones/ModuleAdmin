<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Register page controller.
 * 
 * @since 0.1
 */
class RegisterController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use Users\Traits\UsersTrait;


    /**
     * Display confirm register page to let user click on confirm button.
     * 
     * @return string
     */
    public function confirmAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output = array_merge($output, $this->getConfig(), $Csrf->createToken());

        $output['tokenValue'] = $this->Input->request('token', null);
        @list($userId, $registerConfirmKey) = explode('::', base64_decode($output['tokenValue']));
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $addSince = $UserFieldsDb->get((int) $userId, 'rdbadmin_uf_adduser_waitactivation_since');
        if (!empty($addSince)) {
            $output['showSetPasswordFields'] = true;
        }
        unset($addSince, $Csrf, $registerConfirmKey, $UserFieldsDb, $userId);

        $output['loginUrl'] = $Url->getAppBasedPath() . '/admin/login';
        $output['registerUrl'] = $Url->getAppBasedPath() . '/admin/register' . $Url->getQuerystring();
        $output['registerConfirmUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['registerConfirmMethod'] = 'POST';
        $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath() . '/admin');
        if (stripos($output['gobackUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['gobackUrl'] = $Url->getAppBasedPath() . '/admin';
        } else {
            $output['gobackUrl'] = strip_tags($output['gobackUrl']);
        }
        $output['pageTitle'] = __('Confirm register');
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

            $Assets->addMultipleAssets('css', ['rdta', 'rdbaLoginLogout'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdta', 'lodash', 'rdbaCommon', 'rdbaRegisterConfirm'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaRegisterConfirm',
                'RdbaRegisterC',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'registerUrl' => $output['registerUrl'],
                    'registerConfirmUrl' => $output['registerConfirmUrl'],
                    'registerConfirmMethod' => $output['registerConfirmMethod'],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Register/confirm_v', $output);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// confirmAction


    /**
     * Submit confirm register action.
     * 
     * @return string
     */
    public function doConfirmAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $output = [];
        $output = array_merge($output, $this->getConfig());
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $tokenValue = $this->Input->post('tokenValue');
            @list($userId, $registerConfirmKey) = explode('::', base64_decode($tokenValue));
            unset($tokenValue);

            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
            $where = [];
            $where['user_id'] = $userId;
            $result = $UsersDb->get($where);
            unset($where);

            if (
                !empty($result) && 
                is_object($result) && 
                $result->user_lastlogin == null && // newly register must never logged in.
                $result->user_status == '0' && 
                $result->user_deleted == '0'
            ) {
                // if found user.
                $UserFields = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                $fieldResult = $UserFields->get((int) $userId, 'rdbadmin_uf_registerconfirm_key');
                $addSince = $UserFields->get((int) $userId, 'rdbadmin_uf_adduser_waitactivation_since');
                $formValidated = true;
                $data = [];

                if (!empty($addSince) && is_object($addSince)) {
                    // if this user was add via admin panel and confirmation needed.
                    $DateTimeNow = new \DateTime();
                    $DateTimeNow->setTime(0, 0, 0);
                    $DateTimeAdd = new \DateTime($addSince->field_value);
                    $DateTimeAdd->setTime(0, 0, 0);
                    $DateDiff = $DateTimeAdd->diff($DateTimeNow);

                    if ($DateDiff->invert === 0 && $DateDiff->days <= 2) {
                        // if added user is not older than 2 days.
                        $data['user_password'] = $this->Input->post('user_password');

                        if (empty(trim($data['user_password']))) {
                            $formValidated = false;
                            $output['formResultStatus'] = 'error';
                            $output['formResultMessage'] = __('Please set your password.');
                            http_response_code(400);
                        } elseif ($data['user_password'] !== $this->Input->post('confirm_password')) {
                            $formValidated = false;
                            $output['formResultStatus'] = 'error';
                            $output['formResultMessage'] = __('Your password and confirm does not matched.');
                            http_response_code(400);
                        } else {
                            $data['user_password'] = $UsersDb->hashPassword($data['user_password']);

                            if ($data['user_password'] === false) {
                                // if hash failed.
                                $formValidated = false;
                                $output['formResultStatus'] = 'error';
                                $output['formResultMessage'] = __('The password hashing was error, please contact administrator.');
                                http_response_code(500);

                                if ($this->Container->has('Logger')) {
                                    /* @var $Logger \Rdb\System\Libraries\Logger */
                                    $Logger = $this->Container->get('Logger');
                                    $Logger->write('modules/rdbadmin/controllers/admin/registercontroller', 5, 'Password hash error.');
                                    unset($Logger);
                                }
                            }
                        }
                    } else {
                        // if added user is older than 2 days and no activation.
                        if ($DateDiff->invert === 0 && $DateDiff->days > 2) {
                            // if it really is older than 2 days.
                            // DELETE this user (mark as deleted).
                            $deleteData = [];
                            $deleteData['user_deleted'] = 1;
                            $deleteData['user_deleted_since'] = date('Y-m-d H:i:s');
                            $deleteData['user_deleted_since_gmt'] = gmdate('Y-m-d H:i:s');
                            $UsersDb->update($deleteData, ['user_id' => $userId]);
                            unset($deleteData);
                        }

                        $formValidated = false;
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('User was not found.');
                        http_response_code(404);
                    }

                    unset($DateDiff, $DateTimeAdd, $DateTimeNow);
                }
                unset($addSince);

                if (isset($formValidated) && $formValidated === true) {
                    if (
                        !empty($fieldResult) && 
                        is_object($fieldResult) && 
                        $this->decryptUserFieldsKey($fieldResult->field_value) === $registerConfirmKey
                    ) {
                        // if form validated and register confirm key was matched.
                        $formValidated = true;

                        // change status to 1.
                        $data['user_status'] = 1;
                        $data['user_statustext'] = null;
                    } else {
                        // if key was not matched.
                        $formValidated = false;
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('The registration confirm key was not match, please check your link that is correctly.');
                        http_response_code(400);
                    }
                }// endif;

                if (isset($formValidated) && $formValidated === true) {
                    // if form validated.
                    // delete register confirm key.
                    $deleteRegisterConfirmKeyResult = $UserFields->delete($userId, 'rdbadmin_uf_registerconfirm_key');
                    // delete activation wait since
                    $deleteWaitActivationSince = $UserFields->delete($userId, 'rdbadmin_uf_adduser_waitactivation_since');
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['delete_registerconfirm_key'] = $deleteRegisterConfirmKeyResult;
                        $output['delete_waitactivation_since'] = $deleteWaitActivationSince;
                    }
                    unset($deleteRegisterConfirmKeyResult, $deleteWaitActivationSince);

                    // update users table.
                    $updateUserResult = $UsersDb->update($data, ['user_id' => $userId]);
                    unset($data);

                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['update_user_result'] = $updateUserResult;
                    }
                    unset($updateUserResult);

                    $Url = new \Rdb\System\Libraries\Url($this->Container);
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = sprintf(__('Success, you can use your username and password to login now. Go to %1$slogin page%2$s.'), '<a href="' . $Url->getAppBasedPath() . '/admin/login' . '">', '</a>');
                    unset($Url);
                }// endif;

                unset($formValidated, $UserFields);
            } else {
                // if not found user or maybe confirmed.
                if (is_object($result) && ($result->user_status == '1' || $result->user_lastlogin != null)) {
                    // if confirmed.
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = __('The registration was already confirmed.');
                    http_response_code(409);
                } else {
                    // if not found, deleted.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('User was not found.');
                    http_response_code(404);
                }
            }// endif check user.

            unset($UsersDb);
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
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// doConfirmAction


    /**
     * Submit form register action.
     * 
     * @return string
     */
    public function doRegisterAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $output = [];
        $output = array_merge($output, $this->getConfig());
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (isset($output['configDb']['rdbadmin_UserRegister']) && $output['configDb']['rdbadmin_UserRegister'] !== '1') {
            $output['hideForm'] = true;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Access denied! The registration is currently disabled.');
            http_response_code(403);
            $userRegister = false;
        }

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue]) &&
            (
                !isset($userRegister) ||
                (
                    isset($userRegister) &&
                    $userRegister === true
                )
            )
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $data['user_login'] = trim($this->Input->post('user_login'));
            $data['user_email'] = trim($this->Input->post('user_email'));
            $data['user_password'] = trim($this->Input->post('user_password'));

            if ($this->isUserProxy() === true) {
                // if user is using proxy.
                // let them wait longer.
                sleep(3);
            }

            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

            // validate the form. --------------------------------------------------------
            $data['antibot'] = trim($this->Input->post(\Rdb\Modules\RdbAdmin\Libraries\AntiBot::staticGetHoneypotName()));

            $formValidated = true;
            if (isset($formValidated) && $formValidated === true) {
                /*
                 * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\RegisterController->doRegisterAction.beforeFormValidation
                 * PluginHookDescription: Hook before form validation.
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
                $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.beforeFormValidation', [$data, &$output, &$formValidated]);
                if (!is_array($output)) {
                    $output = $originalOutput;
                }
                if (!is_bool($formValidated)) {
                    $formValidated = $originalFormValidated;
                }
                unset($originalFormValidated, $originalOutput, $Plugins);
            }

            $formValidate = $this->doRegisterFormValidation(($output['configDb'] ?? []), $data);
            unset($data['antibot']);

            if (!empty($formValidate) && isset($formValidate['formResultStatus']) && isset($formValidate['formResultMessage'])) {
                $formValidated = false;
                $output['formResultStatus'] = $formValidate['formResultStatus'];
                $output['formResultMessage'] = $formValidate['formResultMessage'];
                if (isset($formValidate['formFieldsValidation'])) {
                    $output['formFieldsValidation'] = $formValidate['formFieldsValidation'];
                }
                if (isset($formValidate['responseStatus'])) {
                    http_response_code((int) $formValidate['responseStatus']);
                } else {
                    http_response_code(400);
                }
            } else {
                $formValidated = true;
            }
            unset($formValidate);
            // end validate the form. ----------------------------------------------------

            // hash password. -----------------------------------------------------------
            $data['user_password'] = $UsersDb->hashPassword($data['user_password']);
            if ($data['user_password'] === false) {
                // if hash failed.
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('The password hashing was error, please contact administrator.');
                http_response_code(500);

                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/registercontroller', 5, 'Password hash error.');
                    unset($Logger);
                }
            }
            // end hash password. ------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                // if all form validation passed.
                // prepare data for save.
                $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
                $data['user_login'] = $RdbaString->sanitizeUsername($data['user_login']);
                unset($RdbaString);
                $data['user_display_name'] = $data['user_login'];
                $data['user_create'] = date('Y-m-d H:i:s');
                $data['user_create_gmt'] = gmdate('Y-m-d H:i:s');
                $data['user_lastupdate'] = date('Y-m-d H:i:s');
                $data['user_lastupdate_gmt'] = gmdate('Y-m-d H:i:s');
                if (isset($output['configDb']['rdbadmin_UserRegisterVerification'])) {
                    $registerVerification = $output['configDb']['rdbadmin_UserRegisterVerification'];
                }

                if (isset($registerVerification) && $registerVerification == '0') {
                    $data['user_status'] = 1;
                } else {
                    $data['user_status'] = 0;
                    if (isset($registerVerification) && $registerVerification == '1') {
                        $data['user_statustext'] = $UsersDb->predefinedStatusText(0);
                    } else {
                        $data['user_statustext'] = $UsersDb->predefinedStatusText(1);
                    }
                }

                // insert user to DB. ----------------------------------------------------------
                $userId = $UsersDb->add($data);
                if ($userId !== false && $userId > '0') {
                    // if success add user to DB.
                    $successRegister = true;
                    if (isset($registerVerification) && $registerVerification == '1') {
                        // if config need user confirmation.
                        $genKey = $this->generateUserFieldsKey();
                        // prepare data for save in `user_fields` table.
                        $dataFields = [];
                        $dataFields['rdbadmin_uf_registerconfirm_key'] = $genKey['readableKey'];

                        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                        $UserFieldsDb->update($userId, 'rdbadmin_uf_registerconfirm_key', $genKey['encryptedKey'], true);
                        unset($genKey, $UserFieldsDb);
                    }

                    // add default role.
                    if (isset($output['configDb']['rdbadmin_UserRegisterDefaultRoles']) && !empty($output['configDb']['rdbadmin_UserRegisterDefaultRoles'])) {
                        $defaultRoles = explode(',', $output['configDb']['rdbadmin_UserRegisterDefaultRoles']);

                        if (!is_array($defaultRoles) || empty($defaultRoles)) {
                            $defaultRoles = [3];
                        } else {
                            $defaultRoles = array_map('trim', $defaultRoles);
                        }

                        $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
                        $UsersRolesDb->add((int) $userId, $defaultRoles);
                        unset($defaultRoles, $UsersRolesDb);
                    }
                }
                // end insert user to DB. ------------------------------------------------------

                // send emails. -----------------------------------------------------------------
                if (isset($successRegister) && $successRegister === true) {
                    $options = [];
                    if (isset($registerVerification)) {
                        $options['registerVerification'] = $registerVerification;
                    }
                    $options['userId'] = $userId;
                    $options['output'] = $output;
                    // send emails.
                    $sendResult = $this->doRegisterSendEmails($options, $data, ($dataFields ?? []));
                    unset($options);

                    if (isset($sendResult['responseStatus'])) {
                        http_response_code((int) $sendResult['responseStatus']);
                        unset($sendResult['responseStatus']);
                    }
                    $output = array_merge($output, $sendResult);
                    unset($sendResult);
                }
                // end send emails. ------------------------------------------------------------

                if (
                    isset($successRegister) && $successRegister === true &&
                    (
                        !isset($output['formResultMessage']) ||
                        (isset($output['formResultMessage']) && empty($output['formResultMessage']))
                    )
                ) {
                    // if success register but no success message. this is incase that no user confirm register and no admin notifications.
                    // just display success message.
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Your registration was success!') . (isset($registerVerification) && $registerVerification == '0' ? ' ' . __('You can use your username and password to login now.') : '');
                    http_response_code(201);
                }
            }// endif; $formValidated

            unset($data, $dataFields, $formValidated, $registerVerification, $successRegister, $userId, $UsersDb);
        } elseif (
            !isset($userRegister) ||
            (
                isset($userRegister) &&
                $userRegister === true
            )
        ) {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        // remove sensitive info.
        $output = $this->removeSensitiveCfgInfo($output);

        unset($csrfName, $csrfValue, $userRegister);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// doRegisterAction


    /**
     * Form validation for register process.
     * 
     * @param array $configDb The associative array config DB.
     * @param array $data The associative array form data.
     * @return array Return associative array with keys if contain at least one error:<br>
     *                          `formResultStatus` (error),<br>
     *                          `formResultMessage` The result message,<br>
     *                          `formFieldsValidation` (optional) Fields that contain errors,<br>
     *                          `responseStatus` (optional) For http response status,
     */
    private function doRegisterFormValidation(array $configDb, array $data): array
    {
        $errors = [];
        $output = [];

        if (
            empty($data['user_login']) ||
            empty($data['user_email']) ||
            empty($data['user_password'])
        ) {
            // if form validation failed.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Please fill all required form.');
            $output['formFieldsValidation'] = [
                'user_login' => 'required',
                'user_email' => 'required',
                'user_password' => 'required',
            ];
            $output['responseStatus'] = 400;
        } else {
            $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
            if ($RdbaString->sanitizeUsername($data['user_login']) !== $data['user_login']) {
                // if invalid username characters.
                $output['formResultStatus'] = 'error';
                $errors['user_login']['message'] = __('Please enter a valid username.');
                $errors['user_login']['fieldsValidation'] = 'invalid';
            }

            if (filter_var($data['user_email'], FILTER_VALIDATE_EMAIL) === false) {
                // if invalid email.
                $output['formResultStatus'] = 'error';
                $errors['user_email']['message'] = __('Please enter your correct email.');
                $errors['user_email']['fieldsValidation'] = 'invalid';
            }

            if ($RdbaString->sanitizeUsername($data['user_login']) === $data['user_password']) {
                // if username equals to password.
                $output['formResultStatus'] = 'error';
                $errors['user_password']['message'] = __('Username and password could not be same.');
                $errors['user_password']['fieldsValidation'] = 'invalid';
            }
            unset($RdbaString);

            if ($data['user_password'] !== trim($this->Input->post('confirm_password'))) {
                // if password and confirm does not matched.
                $output['formResultStatus'] = 'error';
                $errors['confirm_password']['message'] = __('Your password and confirm does not matched.');
                $errors['confirm_password']['fieldsValidation'] = 'notmatch';
            }

            // validate honeypot (antibot).
            if (!empty($data['antibot'])) {
                $output['formResultStatus'] = 'error';
                $errors[$_SESSION['honeypotName']]['message'] = __('You have entered incorrect data.');// just showing incorrect.
                $errors[$_SESSION['honeypotName']]['fieldsValidation'] = 'invalid';
            }

            // validate disallowed user_login, user_email, user_displayname
            if (
                isset($configDb['rdbadmin_UserRegisterDisallowedName']) && 
                !empty(trim($configDb['rdbadmin_UserRegisterDisallowedName']))
            ) {
                $expDisallowedNames = str_getcsv($configDb['rdbadmin_UserRegisterDisallowedName']);
                foreach ($expDisallowedNames as $disallowedName) {
                    $disallowedName = trim($disallowedName);
                    if (empty($disallowedName)) {
                        continue;
                    }
                    $disallowedName = preg_quote($disallowedName, '#');
                    $disallowedName = str_replace('\*', '(.+)', $disallowedName);

                    if (isset($data['user_login']) && preg_match('#' . $disallowedName . '#i', $data['user_login'])) {
                        $errors['user_login']['message'] = __('Disallowed username.');
                        $errors['user_login']['fieldsValidation'] = 'invalid';
                        break;
                    }
                    if (
                        isset($data['user_email']) && 
                        (
                            preg_match('#' . $disallowedName . '#i', $data['user_email']) || 
                            preg_match('#' . $disallowedName . '#i', $data['user_email'] . '@')// disallowedName@email.tld
                        )
                    ) {
                        $errors['user_email']['message'] = __('Disallowed email.');
                        $errors['user_email']['fieldsValidation'] = 'invalid';
                        break;
                    }
                    if (isset($data['user_display_name']) && preg_match('#' . $disallowedName . '#i', $data['user_display_name'])) {
                        $errors['user_display_name']['message'] = __('Disallowed display name.');
                        $errors['user_display_name']['fieldsValidation'] = 'invalid';
                        break;
                    }
                }// endforeach;
                unset($disallowedName, $expDisallowedNames);
            }

            if (count($errors) == 0) {
                // if found no error.
                // verify username, email must not exists.
                $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

                if (!empty($UsersDb->get(['user_login' => $data['user_login'], 'user_deleted' => '*']))) {
                    $output['formResultStatus'] = 'error';
                    $errors['user_login']['message'] = __('This username is already in use.');
                    $errors['user_login']['fieldsValidation'] = 'invalid';
                }

                if (!empty($UsersDb->get(['user_email' => $data['user_email'], 'user_deleted' => '*']))) {
                    $output['formResultStatus'] = 'error';
                    $errors['user_email']['message'] = __('This email is already in use.');
                    $errors['user_email']['fieldsValidation'] = 'invalid';
                }

                unset($UsersDb);
            }

            // merge error messages.
            if (count($errors) >= 1) {
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
        }// endif; all form fields filled.

        unset($errors);
        return $output;
    }// doRegisterFormValidation


    /**
     * Send emails to user and/or admin.
     * 
     * Send email to user for confirm their register (depend on config) and/or send email to admin to notify (depend on config).<br>
     * It maybe require admin to take action if config is set to verify user register by admin.
     * 
     * You have to make sure that the registration process was complete successfully before calling this method.
     * 
     * @param array $options The associative array maybe contain `successRegister` (bool), `registerVerification` (bool), `userId` (int).
     * @param array $data The data that saved to users table.
     * @param array $dataFields The data that saved to user_fields table.
     * @return array Return associative array with keys:<br>
     *                          `formResultStatus` (success, warning) (optional),<br>
     *                          `formResultMessage` (optional) The result message,<br>
     *                          `responseStatus` (optional) For http response status,
     */
    private function doRegisterSendEmails(array $options, array $data, array $dataFields = []): array
    {
        $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        extract($options);
        if (!isset($output)) {
            $output = [];
        }

        if (
            isset($registerVerification) && $registerVerification == '1'
        ) {
            // if required verification by user themself.
            // send email to user.
            try {
                $tokenValue = base64_encode($userId . '::' . ($dataFields['rdbadmin_uf_registerconfirm_key'] ?? ''));

                // get mailer object.
                $Mail = $Email->getMailer();
                $Mail->addAddress($data['user_email']);
                $Mail->isHTML(true);

                $Mail->Subject = __('Confirmation required.');
                $replaces = [];
                $replaces['%username%'] = $data['user_login'];
                $replaces['%user_login%'] = $data['user_login'];
                $replaces['%registerconfirmlink%'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/register/confirm?token=' . rawurlencode($tokenValue);
                $replaces['%tokenvalue%'] = $tokenValue;
                $emailMessage = $Email->getMessage('RdbAdmin', 'UserRegisterVerification', $replaces);
                unset($replaces);
                $Mail->msgHtml($emailMessage, $Email->baseFolder);
                $Mail->AltBody = $Mail->html2text($emailMessage);
                unset($emailMessage);

                if (defined('APP_ENV') && APP_ENV === 'development') {
                    $output['debug_baseFolderUserVerification'] = $Email->baseFolder;
                }

                $sendResult = $Mail->send();
                if ($sendResult === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Success! Your registration needs confirmation, please open your email and follow instruction.');
                    $output['responseStatus'] = 201;
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = [];
                    $output['formResultMessage'][] = __('Your registration was successfull.');
                    $output['formResultMessage'][] = __('An email could not be sent.');
                    $output['formResultMessage'][] = __('Please contact administrator to confirm your account.');
                    $output['responseStatus'] = 500;
                }
                unset($Mail, $sendResult, $tokenValue);
            } catch (\Exception $e) {
                $output['formResultStatus'] = 'warning';
                $output['formResultMessage'] = [];
                $output['formResultMessage'][] = __('Your registration was successfull.');
                $output['formResultMessage'][] = __('An email could not be sent.') . ' ' . $e->getMessage();
                $output['formResultMessage'][] = __('Please contact administrator to confirm your account.');
                $output['responseStatus'] = 500;
            }// end try..catch
        }// endif; send email to user confirmation.

        if (
            (
                (
                    isset($registerVerification) && 
                    $registerVerification == '2'
                ) || // require admin verify OR
                (
                    isset($output['configDb']['rdbadmin_UserRegisterNotifyAdmin']) && 
                    $output['configDb']['rdbadmin_UserRegisterNotifyAdmin'] == '1'
                )// notify admin on register
            ) 
            &&
            (
                isset($output['configDb']['rdbadmin_UserRegisterNotifyAdminEmails']) &&
                !empty($output['configDb']['rdbadmin_UserRegisterNotifyAdminEmails'])
            )// notify admin emails are not empty
        ) {
            // if (it is required admin to verify user OR notify admin on register) AND admin emails are not empty.
            // send email to admin.
            try {
                // get mailer object.
                $Mail = $Email->getMailer();
                $expRecipients = explode(',', $output['configDb']['rdbadmin_UserRegisterNotifyAdminEmails']);
                $i = 0;
                foreach ($expRecipients as $recipient) {
                    $recipient = trim($recipient);
                    if (!empty($recipient)) {
                        $Mail->addAddress($recipient);
                        $i++;
                    }
                }// endforeach;
                unset($expRecipients, $recipient);
                if ($i == 0) {
                    throw new \SkipEmailAdminException;
                }
                unset($i);
                $Mail->isHTML(true);

                $Mail->Subject = __('There is a user registration.') . (isset($registerVerification) && $registerVerification == '2' ? ' (' . __('Action required.') . ')' : '');
                $replaces = [];
                $replaces['%user_login%'] = $data['user_login'];
                $replaces['%user_email%'] = $data['user_email'];
                $replaces['%registersince%'] = $data['user_create'];
                $replaces['%registersince_gmt%'] = $data['user_create_gmt'];
                if (isset($registerVerification) && $registerVerification == '2') {
                    $replaces['%userneedsadminconfirm%'] = __('The user needs administrator to confirm their registration.') . ' ' . sprintf(__('Please go to %1$sadministrator page%2$s.'), '<a href="' . $Url->getDomainProtocol() . $Url->getAppBasedPath() . '/admin' . '">', '</a>');
                } else {
                    $replaces['%userneedsadminconfirm%'] = sprintf(__('Go to %1$sadministrator page%2$s.'), '<a href="' . $Url->getDomainProtocol() . $Url->getAppBasedPath() . '/admin' . '">', '</a>');
                }
                $emailMessage = $Email->getMessage('RdbAdmin', 'AdminNotifyUserRegister', $replaces);
                unset($replaces);
                $Mail->msgHtml($emailMessage, $Email->baseFolder);
                $Mail->AltBody = $Mail->html2text($emailMessage);
                unset($emailMessage);

                if (defined('APP_ENV') && APP_ENV === 'development') {
                    $output['debug_baseFolderAdminNotify'] = $Email->baseFolder;
                }

                $sendResult = $Mail->send();
                if ($sendResult === true && isset($registerVerification) && $registerVerification == '2') {
                    // if send success and it is require admin verify member.
                    // display message for user to wait admin verify.
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Success! You have to wait for administrator to confirm your registration.');
                    $output['responseStatus'] = 201;
                }
                unset($Mail, $sendResult);
            } catch (\SkipEmailAdminException $ske) {
                // just skip.
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/registercontroller', 1, 'email to admin was skipped.');
                    unset($Logger);
                }
            } catch (\Exception $e) {
                if (
                    isset($registerVerification) && 
                    $registerVerification == '2' && 
                    (
                        !isset($output['formResultMessage']) ||
                        (
                            isset($output['formResultMessage']) &&
                            empty($output['formResultMessage'])
                        )
                    )
                ) {
                    // if failed to send and it is require admin verify member.
                    // display register success, email could not be sent, please contact admin directly.
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = [];
                    $output['formResultMessage'][] = __('Your registration was successfull.');
                    $output['formResultMessage'][] = __('An email could not be sent.') . ' ' . $e->getMessage();
                    $output['formResultMessage'][] = __('Please contact administrator to confirm your account.');
                    $output['responseStatus'] = 500;
                }

                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/registercontroller', 4, 'An error has been occured while trying to send email to admin.', $e->getMessage());
                    unset($Logger);
                }
            }// end try..catch
        }

        unset($Email, $Url);

        return $output;
    }// doRegisterSendEmails


    /**
     * Get common use configuration between methods.
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
            'rdbadmin_UserRegisterNotifyAdmin',
            'rdbadmin_UserRegisterNotifyAdminEmails',
            'rdbadmin_UserRegisterVerification',
            'rdbadmin_UserRegisterDisallowedName',
            'rdbadmin_UserRegisterDefaultRoles',
        ];
        $configDefaults = [
            '',
            '',
            '0',
            '0',
            '',
            '0',
            '',
            '3',
        ];

        $output = [];
        $output['configDb'] = $ConfigDb->get($configNames, $configDefaults);
        unset($ConfigDb, $configDefaults, $configNames);

        return $output;
    }// getConfig


    /**
     * Display register page.
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

        if (isset($output['configDb']['rdbadmin_UserRegister']) && $output['configDb']['rdbadmin_UserRegister'] !== '1') {
            $output['hideForm'] = true;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Access denied! The registration is currently disabled.');
            http_response_code(403);
        }

        // honeypot (antibot)
        $AntiBot = new \Rdb\Modules\RdbAdmin\Libraries\AntiBot();
        $output['honeypotName'] = $AntiBot->setAndGetHoneypotName();
        unset($AntiBot);

        $output['loginUrl'] = $Url->getAppBasedPath() . '/admin/login' . $Url->getQuerystring();
        $output['registerUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['registerMethod'] = 'POST';
        $output['gobackUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath() . '/admin');
        if (stripos($output['gobackUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['gobackUrl'] = $Url->getAppBasedPath() . '/admin';
        } else {
            $output['gobackUrl'] = strip_tags($output['gobackUrl']);
        }
        $output['pageTitle'] = __('Create new account');
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
            $Assets->addMultipleAssets('js', ['rdbaRegister'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaRegister',
                'RdbaRegister',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'registerUrl' => $output['registerUrl'],
                    'registerMethod' => $output['registerMethod'],
                    'gobackUrl' => $output['gobackUrl'],
                ]
            );

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Register/index_v', $output);

            unset($Assets, $MyModuleAssets, $Url);
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


}
