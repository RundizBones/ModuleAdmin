<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Add user controller.
 * 
 * @since 0.1
 */
class AddController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    /**
     * Do add new user action.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['add']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDbUser();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $data['user_login'] = trim($this->Input->post('user_login'));
            $data['user_email'] = trim($this->Input->post('user_email'));// no filter email here, it will be validate later.
            $data['user_password'] = trim($this->Input->post('user_password'));
            $data['user_display_name'] = trim($this->Input->post('user_display_name'));
            $data['user_status'] = intval(trim($this->Input->post('user_status', 0)));
            $data['user_statustext'] = trim($this->Input->post('user_statustext'));

            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

            if ($data['user_login'] === '') {
                if ($data['user_email'] !== '') {
                    $data['user_login'] = $data['user_email'];
                } else {
                    $data['user_login'] = null;
                }
            }
            if ($data['user_email'] === '') {
                $data['user_email'] = null;
            }
            if ($data['user_display_name'] === '') {
                if ($data['user_login'] !== '') {
                    $data['user_display_name'] = $data['user_login'];
                } else {
                    $data['user_display_name'] = null;
                }
            }
            if ($data['user_statustext'] === '') {
                $data['user_statustext'] = null;
            }
            if ($data['user_status'] === 1) {
                $data['user_statustext'] = null;
            } elseif ($data['user_status'] !== 1 && $data['user_status'] !== 0) {
                $data['user_status'] = 0;
            }
            if ($this->Input->post('notify_user') === '1') {
                $data['user_status'] = 0;
                $data['user_statustext'] = $UsersDb->predefinedStatusText(0);
            }

            $dataFields = [];
            $dataUsersRoles = [];
            $dataUsersRoles['roleIds'] = $this->Input->post('user_roles');

            if (empty($dataUsersRoles['roleIds'])) {
                $configDefaultRoles = explode(',', $output['configDb']['rdbadmin_UserRegisterDefaultRoles']);
                $configDefaultRoles = array_map('trim', $configDefaultRoles);
                $dataUsersRoles['roleIds'] = $configDefaultRoles;
                unset($configDefaultRoles);
            }

            // validate the form. --------------------------------------------------------
            $data['confirm_password'] = $this->Input->post('confirm_password');
            $formValidate = $this->addUpdateUserFormValidation($data, $dataFields, $dataUsersRoles);
            unset($data['confirm_password']);

            if (!empty($formValidate) && isset($formValidate['formResultStatus']) && isset($formValidate['formResultMessage'])) {
                // if contain form validation errors.
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
                // if all passed.
                $formValidated = true;
            }
            unset($formValidate);
            // end validate the form. ----------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                // try to hash the password if it will be success.
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
                        $Logger->write('modules/rdbadmin/controllers/admin/users/addcontroller', 5, 'Password hash error.');
                        unset($Logger);
                    }
                }
                // end hash password. ------------------------------------------------------
            }

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                // sanitize values.
                $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
                if (!is_null($data['user_login'])) {
                    $data['user_login'] = $RdbaString->sanitizeUsername($data['user_login']);
                }
                if (!is_null($data['user_display_name'])) {
                    $data['user_display_name'] = $RdbaString->sanitizeDisplayname($data['user_display_name']);
                }
                unset($RdbaString);

                // insert user to DB. ----------------------------------------------------------
                try {
                    $userId = $UsersDb->add($data);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage() . '<br>' . PHP_EOL . $ex->getTraceAsString();
                    $userId = false;
                }

                if ($userId !== false && $userId > '0') {
                    // if success add user to DB.
                    $successRegister = true;

                    if ($this->Input->post('notify_user') === '1') {
                        // if notify user, this means user needs to click confirmation/activation link on emails.
                        // code in this condition are copied from /Admin/RegisterController.php in `doRegisterAction()` method.
                        $genKey = $this->generateUserFieldsKey();
                        // prepare data for save in `user_fields` table.
                        $dataFields['rdbadmin_uf_registerconfirm_key'] = $genKey['readableKey'];
                        $dataFields['rdbadmin_uf_adduser_waitactivation_since'] = date('Y-m-d H:i:s');

                        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                        $UserFieldsDb->update($userId, 'rdbadmin_uf_registerconfirm_key', $genKey['encryptedKey'], true);
                        $UserFieldsDb->update($userId, 'rdbadmin_uf_adduser_waitactivation_since', $dataFields['rdbadmin_uf_adduser_waitactivation_since'], true);
                        unset($genKey, $UserFieldsDb);
                    }

                    // add roles.
                    $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
                    $UsersRolesDb->add((int) $userId, $dataUsersRoles['roleIds']);
                    unset($UsersRolesDb);
                }
                // end insert user to DB. ------------------------------------------------------

                // send emails. -----------------------------------------------------------------
                if (
                    $this->Input->post('notify_user') === '1' && 
                    isset($successRegister) && 
                    $successRegister === true
                ) {
                    // if success and form is ticked to send email to notify user about their account.
                    // send emails.
                    $dataSendEmails = $data;
                    $dataSendEmails['user_id'] = $userId;
                    $sendResult = $this->doAddSendEmails($dataSendEmails, ($dataFields ?? []));
                    if (isset($sendResult['responseStatus'])) {
                        http_response_code((int) $sendResult['responseStatus']);
                        unset($sendResult['responseStatus']);
                    }
                    $output = array_merge($output, $sendResult);
                    unset($dataSendEmails, $sendResult);
                }
                // end send emails. ------------------------------------------------------------

                if (isset($successRegister) && $successRegister === true) {
                    // if success.
                    if (
                            !isset($output['formResultMessage']) ||
                            (isset($output['formResultMessage']) && empty($output['formResultMessage']))
                    ) {
                        // if success but no success message.
                        // just display success message.
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('New user created.') . ' <a class="rdba-listpage-edit" href="' . $Url->getAppBasedPath(true) . '/admin/users/edit/' . $userId . '">' . __('Edit user') . '</a>';
                        http_response_code(201);
                    }// endif;

                    $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/users';

                    if (isset($output['formResultMessage'])) {
                        // if there is success message now.
                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                    }
                } else {
                    // if not success.
                    if (isset($output['errorMessage'])) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = $output['errorMessage'];
                        http_response_code(500);
                    }
                }// endif;
            }// endif; last formvalidation passed.

            unset($data, $dataFields, $dataUsersRoles, $formValidated, $successRegister, $userId, $UsersDb);
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
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doAddAction


    /**
     * Send an email to notify user about their account.
     * 
     * @param array $data The data that saved to users table.
     * @param array $dataFields The data that saved to user_fields table.
     * @return array Return associative array with keys:<br>
     *                          `formResultStatus` (success, warning) (optional),<br>
     *                          `formResultMessage` (optional) The result message,<br>
     *                          `responseStatus` (optional) For http response status,
     */
    protected function doAddSendEmails(array $data, array $dataFields = []): array
    {
        $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $output = [];

        try {
            // the code in this block is copied from /Admin/RegisterController.php in `doRegisterSendEmails()` method.
            $tokenValue = base64_encode($data['user_id'] . '::' . ($dataFields['rdbadmin_uf_registerconfirm_key'] ?? ''));

            // get mailer object.
            $Mail = $Email->getMailer();
            $Mail->addAddress($data['user_email']);
            $Mail->isHTML(true);

            $Mail->Subject = __('Your user account information');
            $replaces = [];
            $replaces['%username%'] = $data['user_login'];
            $replaces['%user_login%'] = $data['user_login'];
            $replaces['%registerconfirmlink%'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/register/confirm?token=' . rawurlencode($tokenValue);
            $replaces['%tokenvalue%'] = $tokenValue;
            $emailMessage = $Email->getMessage('RdbAdmin', 'AdminAddUserNeedsVerification', $replaces);
            unset($replaces);
            $Mail->msgHtml($emailMessage, $Email->baseFolder);
            $Mail->AltBody = $Mail->html2text($emailMessage);
            unset($emailMessage);

            $sendResult = $Mail->send();

            if ($sendResult === true) {
                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = __('New user created. The new user needs confirmation and the email has been sent.');
                $output['responseStatus'] = 201;
            } else {
                throw new \Exception('');
            }

            unset($Mail, $sendResult, $tokenValue);
        } catch (\Exception $e) {
            $output['formResultStatus'] = 'warning';
            $output['formResultMessage'] = [];
            $output['formResultMessage'][] = __('New user created.');
            $output['formResultMessage'][] = __('An email could not be sent.') . ' ' . $e->getMessage();
            $output['formResultMessage'][] = __('Your user could not confirm account via the link in email.');
            $output['responseStatus'] = 500;
        }// end try..catch

        unset($Email, $Url);
        return $output;
    }// doAddSendEmails


    /**
     * Get configuration from DB about user.
     */
    protected function getConfigDbUser(): array
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configNames = [
            'rdbadmin_UserRegisterDefaultRoles',
        ];
        $configDefaults = [
            '3',
        ];

        $output = $ConfigDb->get($configNames, $configDefaults);

        unset($ConfigDb, $configDefaults, $configNames);
        return $output;
    }// getConfigDbUser


    /**
     * Get list of roles (for display in select box in user management only).
     * 
     * @return array
     */
    protected function getRoles(): array
    {
        $output = [];
        $options = [];
        $options['unlimited'] = true;
        $options['where'] = [
            'userrole_priority' => '< 10000',
        ];
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $output['listRoles'] = $UserRolesDb->listItems($options);

        unset($options, $UserRolesDb);
        return $output;
    }// getRoles


    /**
     * Add new user page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['add']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['configDb'] = array_merge($output['configDb'], $this->getConfigDbUser());

        $output = array_merge($output, $this->getRoles());
        $output = array_merge($output, $this->getUserUrlsMethods());
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['pageTitle'] = __('Add new user');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // pre-define form values.
        $output['user_status'] = '1';
        $output['notify_user'] = '1';
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $output['predefinedStatusTexts'] = $UsersDb->predefinedStatusText();
        unset($UsersDb);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            //$Assets->addMultipleAssets('css', [], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaUsersAdd', 'rdbaHistoryState'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUsersAdd',
                'RdbaUsers',// must be the same with datatable page because we can use ajax this page in dialog.
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                ], $this->getUserUrlsMethods())
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            include_once dirname(dirname(dirname(__DIR__))) . '/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Users/add_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
