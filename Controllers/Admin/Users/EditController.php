<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Edit user controller.
 * 
 * @since 0.1
 */
class EditController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    use Traits\UsersEditingTrait;


    /**
     *
     * @var array User fields that must not be update via the REST API (at least with this controller). refer these fields from `UserFIeldsDb->rdbaUserFields` property. 
     */
    protected $preventUpdateFields = [
        'rdbadmin_uf_adduser_waitactivation_since',
        'rdbadmin_uf_admindashboardwidgets_order',
        'rdbadmin_uf_changeemail_key',
        'rdbadmin_uf_changeemail_time',
        'rdbadmin_uf_changeemail_value',
        'rdbadmin_uf_changeemail_history',
        'rdbadmin_uf_login2stepverification_key',
        'rdbadmin_uf_login2stepverification_time',
        'rdbadmin_uf_login2stepverification_tmpdata',
        'rdbadmin_uf_registerconfirm_key',
        'rdbadmin_uf_resetpassword_key',
        'rdbadmin_uf_resetpassword_time',
        'rdbadmin_uf_simultaneouslogin_reset_key',
        'rdbadmin_uf_simultaneouslogin_reset_time',
        'rdbadmin_uf_avatar',
    ];


    /**
     * Confirm change email.
     * 
     * This method was called from `indexAction()` method.
     * 
     * @param int $user_id
     * @return array Return associative array with keys:<br>
     *                          `formResultStatus` (error or success),<br>
     *                          `formResultMessage` (error or success),<br>
     *                          `responseStatus` (error only),
     */
    protected function doConfirmChangeEmail(int $user_id, $token): array
    {
        $output = [];
        @list($userId, $changeEmailKey) = explode('::', base64_decode($token));

        if ($userId != $user_id) {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Access denied! You are trying to do the action that is not base on your account.');
            $output['responseStatus'] = 403;
            return $output;
        }

        unset($userId);

        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

        $keyResult = $UserFieldsDb->get($user_id, 'rdbadmin_uf_changeemail_key');
        $keyDate = $UserFieldsDb->get($user_id, 'rdbadmin_uf_changeemail_time');
        $newEmailResult = $UserFieldsDb->get($user_id, 'rdbadmin_uf_changeemail_value');

        $keyValidated = false;

        if (
            is_object($keyResult) && 
            isset($keyResult->field_value) &&
            is_object($keyDate) && 
            isset($keyDate->field_value) &&
            is_object($newEmailResult) && 
            isset($newEmailResult->field_value)
        ) {
            $NowDt = new \DateTime();
            $FieldDt = new \DateTime($keyDate->field_value);

            if (
                $changeEmailKey === $this->decryptUserFieldsKey($keyResult->field_value) &&
                $FieldDt > $NowDt
            ) {
                $keyValidated = true;
                $newEmail = $newEmailResult->field_value;
            }
        }

        unset($FieldDt, $NowDt);


        if (isset($keyValidated) && $keyValidated === true) {
            $result = $UsersDb->get(['user_id' => $user_id, 'user_status' => 1]);
            if (empty($result) || $result === false) {
                // if user is not enabled or deleted
                $keyValidated = false;
            } else {
                $previousEmail = $result->user_email;
            }
            unset($result);
        }

        if (isset($keyValidated) && $keyValidated === true) {
            $data = [];
            $data['user_email'] = $newEmail;
            $keyValidated = $UsersDb->update($data, ['user_id' => $user_id]);
            unset($data);

            if ($keyValidated === true) {
                // if updated users table.
                $UserFieldsDb->delete($user_id, 'rdbadmin_uf_changeemail_value');
                $UserFieldsDb->delete($user_id, 'rdbadmin_uf_changeemail_key');
                $UserFieldsDb->delete($user_id, 'rdbadmin_uf_changeemail_time');

                $dataFields = $this->doUpdateAddChangedEmailToHistoryVar($user_id, $previousEmail);
                $UserFieldsDb->update($user_id, 'rdbadmin_uf_changeemail_history', $dataFields['rdbadmin_uf_changeemail_history'], true);
                unset($dataFields);
            }

            unset($newEmail);
        }

        if (!isset($keyValidated) || (isset($keyValidated) && $keyValidated === false)) {
            $output['responseStatus'] = 403;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token or your token may expired.');
        } else {
            $output['formResultStatus'] = 'success';
            $output['formResultMessage'] = __('Your email was changed.');
        }

        unset($keyDate, $keyResult, $keyValidated, $newEmailResult);

        unset($UserFieldsDb, $UsersDb);

        return $output;
    }// doConfirmChangeEmail


    /**
     * Update user data.
     * 
     * @param string $user_id
     * @return string
     */
    public function doUpdateAction($user_id = ''): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (!$this->isMe($user_id)) {
            $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDbUser();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            $isInDataTablesPage = (isset($_PATCH['isInDataTablesPage']) && $_PATCH['isInDataTablesPage'] === 'true' ? true : false);
            unset($_PATCH['isInDataTablesPage']);

            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);
            // prepare data for checking.
            $data = $this->doUpdateGetData();
            $dataFields = $this->doUpdateGetDataFields();
            $dataUsersRoles = $this->doUpdateGetDataUsersRoles();

            // remove fields that is unable to update manually.
            $this->doUpdateRemoveUnUpdatableDataFields($dataFields);

            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
            $user_login = $data['user_login'];

            // get user data and if user is not exists then make form invalid. ------
            $where = [];
            $where['user_id'] = $user_id;
            $userRow = $UsersDb->get($where);
            unset($where);

            if (empty($userRow)) {
                http_response_code(404);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Not found selected user.');
                $formValidated = false;
                unset($userRow);
            }
            // end get user data. -------------------------------------------------------

            // validate the form. --------------------------------------------------------
            if (!isset($formValidated) || isset($formValidated) && $formValidated === true) {
                unset($data['user_login']);

                // validate form field must contain and correct value.
                $formValidation = $this->addUpdateUserFormValidation($data, $dataFields, $dataUsersRoles, 'update', $user_id);

                if (!empty($formValidation) && isset($formValidation['formResultStatus']) && isset($formValidation['formResultMessage'])) {
                    // if contain form validation errors.
                    $formValidated = false;
                    $output['formResultStatus'] = $formValidation['formResultStatus'];
                    $output['formResultMessage'] = $formValidation['formResultMessage'];
                    if (isset($formValidation['formFieldsValidation'])) {
                        $output['formFieldsValidation'] = $formValidation['formFieldsValidation'];
                    }
                    if (isset($formValidation['responseStatus'])) {
                        http_response_code((int) $formValidation['responseStatus']);
                    } else {
                        http_response_code(400);
                    }
                } else {
                    // if all passed.
                    $formValidated = true;
                }
                unset($formValidation);
            }

            if (isset($formValidated) && $formValidated === true) {
                if ($user_id <= 0) {
                    $formValidated = false;
                    http_response_code(403);
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Unable to edit guest user.');
                }
            }

            if (isset($formValidated) && $formValidated === true) {
                // if form validated.
                // check not editing higher role. ---------------------------------
                // make sure that this is not editing user that has higher role priority.
                if ($this->isEditingHigherRole($user_id) === true) {
                    $formValidated = false;
                    http_response_code(403);
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
                }
                // end check not editing higher role. -----------------------------
            }
            // end validate the form. ----------------------------------------------------

            if (isset($formValidated) && $formValidated === true && array_key_exists('user_password', $data)) {
                // if form validation passed.
                if (!is_null($data['user_password']) && !empty(trim($data['user_password']))) {
                    // if password is not empty (it is changing).
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
                } else {
                    unset($data['user_password']);
                }
            }

            if (isset($formValidated) && $formValidated === true && array_key_exists('user_email', $data)) {
                if (!empty(trim($data['user_email']))) {
                    // if user enter email.
                    $editingSelf = false;
                    if (
                        isset($this->userSessionCookieData['user_id']) && 
                        intval($this->userSessionCookieData['user_id']) === intval($user_id)
                    ) {
                        $editingSelf = true;
                    }

                    // process email change -------------------------------------------------------
                    if (isset($userRow->user_email) && $userRow->user_email !== $data['user_email']) {
                        // if email change from the user.
                        $newEmail = $data['user_email'];
                        $notifyEmailChanged = true;

                        if ($editingSelf === true) {
                            // if editing self.
                            if (isset($output['configDb']['rdbadmin_UserConfirmEmailChange']) && $output['configDb']['rdbadmin_UserConfirmEmailChange'] === '1') {
                                // if config `rdbadmin_UserConfirmEmailChange` is set to `1` (verify by user email).
                                // generate confirm key.
                                $genKey = $this->generateUserFieldsKey();
                                $dataFields['rdbadmin_uf_changeemail_value'] = $data['user_email'];
                                $dataFields['rdbadmin_uf_changeemail_key'] = $genKey['encryptedKey'];
                                $DateTime = new \DateTime();
                                $DateTime->add(new \DateInterval('PT' . ($output['configDb']['rdbadmin_UserConfirmWait'] ?? 10) . 'M'));
                                $dataFields['rdbadmin_uf_changeemail_time'] = $DateTime->format('Y-m-d H:i:s');
                                $changeEmailKey = $genKey['readableKey'];
                                unset($DateTime, $genKey);

                                // send verification email.
                                $sendResult = $this->doUpdateSendEmailChanging([
                                    'changeEmailOf' => 'self',
                                    'user_id' => $user_id,
                                    'user_login' => $user_login,
                                    'user_email' => $newEmail,
                                    'readableChangeEmailKey' => $changeEmailKey,
                                ]);

                                if (isset($sendResult['responseStatus'])) {
                                    http_response_code((int) $sendResult['responseStatus']);
                                    unset($sendResult['responseStatus']);
                                }
                                if (!isset($sendResult['success']) || $sendResult['success'] !== true) {
                                    // if failed to send confirmation emails to myself.
                                    $output = array_merge($output, $sendResult);
                                    $stopSendEmail = true;
                                    $formValidated = false;
                                }

                                $notifyEmailChanged = false;// not notify because user must click on confirm link.
                                unset($data['user_email'], $sendResult);
                            }// endif config was set to user verification = 1.
                        }// endif editing self;

                        if (
                            isset($notifyEmailChanged) && 
                            $notifyEmailChanged === true && 
                            (!isset($stopSendEmail) || $stopSendEmail !== true)
                        ) {
                            // send notification email.
                            $sendResult = $this->doUpdateSendEmailChanging([
                                'changeEmailOf' => 'other',
                                'user_id' => $user_id,
                                'user_login' => $user_login,
                                'user_email' => $newEmail,
                                'previous_email' => $userRow->user_email,
                                'admin_email' => ($output['configDb']['rdbadmin_UserRegisterNotifyAdminEmails'] ?? ''),
                            ]);

                            if (isset($sendResult['responseStatus'])) {
                                http_response_code((int) $sendResult['responseStatus']);
                                unset($sendResult['responseStatus']);
                            }
                            if (!isset($sendResult['success']) || $sendResult['success'] !== true) {
                                // if failed to send email but email was changed in db.
                                $output = array_merge($output, $sendResult);
                                $formValidated = true;
                            }
                            unset($sendResult);

                            if ($formValidated === true) {
                                // if send email success and email has been changed, add change email to email history list.
                                $emailHistory = $this->doUpdateAddChangedEmailToHistoryVar((int) $user_id, $userRow->user_email);
                                $dataFields = array_merge($dataFields, $emailHistory);
                                unset($emailHistory);
                            }
                        }// endif; notify email changed.

                        unset($newEmail, $notifyEmailChanged, $stopSendEmail);
                    }// endif email change from user.
                    // end process email change ---------------------------------------------------

                    unset($editingSelf);
                }// endif; user entered email
            }

            // remove unchangable data.
            $this->doUpdateRemoveUnchangableData($data);

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                // update user to DB. ----------------------------------------------------------
                try {
                    $output['updateUsers'] = $UsersDb->update($data, ['user_id' => (int) $user_id]);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage() . '<br>' . PHP_EOL . $ex->getTraceAsString();
                    $output['updateUsers'] = false;
                }

                if ($output['updateUsers'] === true) {
                    // if update user in users table was success.
                    $successUpdate = true;

                    // update user fields.
                    $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                    if (isset($dataFields) && is_array($dataFields) && !empty($dataFields)) {
                        $output['updateUserFields'] = [];
                        foreach ($dataFields as $fieldName => $fieldValue) {
                            if ($fieldValue === '') {
                                $fieldValue = null;
                            }
                            $output['updateUserFields'][$fieldName] = $UserFieldsDb->update((int) $user_id, $fieldName, $fieldValue, true);
                        }// endforeach;
                        unset($fieldName, $fieldValue);
                    }

                    // update roles.
                    $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
                    $output['updateUsersRoles'] = $UsersRolesDb->update((int) $user_id, ($dataUsersRoles['roleIds'] ?? []));

                    unset($UserFieldsDb, $UsersRolesDb);
                }

                if (defined('APP_ENV') && APP_ENV === 'development') {
                    $output['debug_usersData'] = $data;
                    $output['debug_userFieldsData'] = $dataFields;
                    $output['debug_usersRolesData'] = $dataUsersRoles;
                }
                // end update user to DB. ------------------------------------------------------

                if (isset($successUpdate) && $successUpdate === true) {
                    // if success.
                    if (
                        !isset($output['formResultMessage']) ||
                        (isset($output['formResultMessage']) && empty($output['formResultMessage']))
                    ) {
                        // if success but no success message.
                        // just display success message.
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('User updated.');
                        http_response_code(200);
                    }// endif;

                    if (isset($isInDataTablesPage) && $isInDataTablesPage === true) {
                        // if user is in datatables page and using ajax editing in dialog. this should be write to session and redirect back to datatable page.
                        $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/users';

                        if (isset($output['formResultMessage'])) {
                            // if there is success message now.
                            $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                            unset($output['formResultMessage'], $output['formResultStatus']);
                        }
                    }
                } else {
                    // if not success.
                    if (isset($output['errorMessage'])) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = $output['errorMessage'];
                        http_response_code(500);
                    }
                }
            }// endif; last formvalidation passed.

            unset($data, $dataFields, $dataUsersRoles, $formValidated, $successUpdate, $user_login, $UsersDb, $userRow);
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
    }// doUpdateAction


    /**
     * Add previous email to history variable that is ready to update in next step.
     * 
     * This method was called from `doUpdateAction()`, `doConfirmChangeEmail()` methods.
     * 
     * @param int $user_id
     * @param string $previousEmail
     * @return array Return added previous email to array that key is field name and ready to update.
     */
    protected function doUpdateAddChangedEmailToHistoryVar(int $user_id, string $previousEmail): array
    {
        $dataFields = [];

        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);

        $emailHistoryField = $UserFieldsDb->get($user_id, 'rdbadmin_uf_changeemail_history');
        if (is_object($emailHistoryField)) {
            $emailHistory = $emailHistoryField->field_value;
            if (!is_array($emailHistory)) {
                $emailHistory = [];
            } else {
                // if history is array
                // remove first xx items.
                // set to 99 because we will keep only 100 last changed.
                // remove array items from end (first xx items) because newest will be first using `array_unshift()`.
                $emailHistory = array_slice($emailHistory, 0, 99);
            }
        } else {
            $emailHistory = [];
        }
        unset($emailHistoryField);

        array_unshift($emailHistory, ['email' => $previousEmail, 'date' => date('Y-m-d H:i:s'), 'gmtdate' => gmdate('Y-m-d H:i:s')]);
        $dataFields['rdbadmin_uf_changeemail_history'] = $emailHistory;

        unset($UserFieldsDb);

        return $dataFields;
    }// doUpdateAddChangedEmailToHistoryVar


    /**
     * Get and set data for `users` table.
     * 
     * This method was called from `doUpdateAction()` method.
     * 
     * @global array $_PATCH
     * @return array
     */
    protected function doUpdateGetData(): array
    {
        global $_PATCH;

        $data = [];

        foreach ($_PATCH as $name => $value) {
            $data[$name] = $value;
            if (
                is_string($value) && 
                trim($value) === ''
            ) {
                $data[$name] = null;
            }
        }// endforeach;
        unset($name, $value);

        if (isset($data['user_display_name']) && !empty($data['user_display_name'])) {
            // if display name was set and not empty.
            // sanitize display name.
            $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
            $data['user_display_name'] = $RdbaString->sanitizeDisplayname($data['user_display_name']);
            unset($RdbaString);
        }

        if (
            isset($data['user_display_name']) && 
            isset($data['user_login']) && 
            trim($data['user_display_name']) === ''
        ) {
            // if display name was set and empty while user login is set
            if (trim($data['user_login']) !== '') {
                $data['user_display_name'] = $data['user_login'];
            }
        }

        if (
            array_key_exists('user_display_name', $data) && 
            (
                is_null($data['user_display_name']) ||
                trim($data['user_display_name']) === ''
            )
        ) {
            // if display name is null or still empty.
            // unset this (cannot change) because we do not allow display name to be null or empty.
            unset($data['user_display_name']);
        }

        if (isset($data['user_status'])) {
            $data['user_status'] = intval($data['user_status']);
            if (isset($data['user_status']) && $data['user_status'] === 1) {
                $data['user_statustext'] = null;
            } elseif (isset($data['user_status']) && $data['user_status'] !== 1 && $data['user_status'] !== 0) {
                $data['user_status'] = 0;
            }
        }

        return $data;
    }// doUpdateGetData


    /**
     * Get and set data for `user_fields` table.
     * 
     * This method was called from `doUpdateAction()` method.
     * 
     * @global array $_PATCH
     * @return array
     */
    protected function doUpdateGetDataFields(): array
    {
        global $_PATCH;

        $dataFields = [];

        if (isset($_PATCH['user_fields'])) {
            $dataFields = $_PATCH['user_fields'];
        }

        return $dataFields;
    }// doUpdateGetDataFields


    /**
     * Get and set data for `users_roles` table.
     * 
     * This method was called from `doUpdateAction()` method.
     * 
     * @global array $_PATCH
     * @return array
     */
    protected function doUpdateGetDataUsersRoles(): array
    {
        global $_PATCH;

        $dataUsersRoles = [];

        if (isset($_PATCH['user_roles']) && !empty($_PATCH['user_roles'])) {
            $dataUsersRoles['roleIds'] = $_PATCH['user_roles'];
        }

        return $dataUsersRoles;
    }// doUpdateGetDataUsersRoles


    /**
     * Remove fields that is unable to update manually to user_fields table.
     * 
     * This method was called from `doUpdateAction()` method.
     * 
     * @param array $dataFields
     */
    protected function doUpdateRemoveUnUpdatableDataFields(array &$dataFields)
    {
        $prefixUf = 'rdbadmin_uf_';

        foreach ($dataFields as $fieldName => $fieldValue) {
            if (in_array(strtolower($fieldName), array_map('strtolower', $this->preventUpdateFields))) {
                unset($dataFields[$fieldName]);
            } else {
                $detectedPrefix = substr($fieldName, 0, strlen($prefixUf));
                if ($detectedPrefix !== $prefixUf) {
                    unset($dataFields[$fieldName]);
                }
                unset($detectedPrefix);
            }
        }// endforeach;
        unset($fieldName, $fieldValue, $prefixUf);
    }// doUpdateRemoveUnUpdatableDataFields


    /**
     * Remove unchangable data.
     * 
     * Remove data that contain column name that must not be update.<br>
     * This method was called from `doUpdateAction()` method.
     * 
     * @param array $data
     */
    protected function doUpdateRemoveUnchangableData(array &$data)
    {
        unset(
            $data['user_id'], 
            $data['user_login'], 
            $data['confirm_password'],
            $data['user_create'], 
            $data['user_create_gmt'], 
            $data['user_lastlogin'], 
            $data['user_lastlogin_gmt'], 
            $data['user_deleted'], 
            $data['user_deleted_since'], 
            $data['user_deleted_since_gmt'],
            $data['user_fields'],
            $data['user_roles']
        );
    }// doUpdateRemoveUnchangableData


    /**
     * Send email to notify or confirmation for changing email.
     * 
     * Send an email to request confirmation for changing self email.<br>
     * Or send a notification email for changing others email.
     * 
     * @param array $options The options keys: <br>
     *                          'changeEmailOf' (required) value is 'self', 'other'.<br>
     *                          'user_id' (required) Target user ID.<br>
     *                          'user_login' (required) Target username.<br>
     *                          'user_email' (required) Target email.<br>
     *                          Below is for send verification emails (email require confirm link clicked).<br>
     *                          'readableChangeEmailKey' For change self email that must click on confirmation link with key only.<br>
     *                          Below is for send notification emails.<br>
     *                          'previous_email' For send to notify change 'other' email by sending to their previous email. An error will be thrown if not specify for send notify email.<br>
     *                          'admin_email' For send to notify change 'other' email.<br>
     * @return array Return associative array with keys:<br>
     *                          `success` (success only),<br>
     *                          `formResultStatus` (error only),<br>
     *                          `formResultMessage` (error only) The result message,<br>
     *                          `responseStatus` (error only) For HTTP response status,
     * @throws \Exception Throw errors if no required options.
     */
    protected function doUpdateSendEmailChanging(array $options = []): array
    {
        $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $output = [];

        if (
            !isset($options['changeEmailOf']) ||
            (
                isset($options['changeEmailOf']) &&
                (
                    $options['changeEmailOf'] !== 'self' &&
                    $options['changeEmailOf'] !== 'other'
                )
            )
        ) {
            throw new \InvalidArgumentException('The argument options required the key `changeEmailOf` and its value must be `self` or `other`.');
        } elseif (!isset($options['user_id'])) {
            throw new \InvalidArgumentException('The argument options required the key `user_id`.');
        } elseif (!isset($options['user_login'])) {
            throw new \InvalidArgumentException('The argument options required the key `user_login`.');
        } elseif (!isset($options['user_email'])) {
            throw new \InvalidArgumentException('The argument options required the key `user_email`.');
        }

        if ($options['changeEmailOf'] === 'other' && !isset($options['previous_email'])) {
            throw new \InvalidArgumentException('The argument options required the key `previous_email` for sending email to notify others email change.');
        }

        try {
            $tokenValue = base64_encode($options['user_id'] . '::' . ($options['readableChangeEmailKey'] ?? ''));

            // get mailer object.
            $Mail = $Email->getMailer();
            if ($options['changeEmailOf'] === 'self') {
                $Mail->addAddress($options['user_email']);
            } else {
                $Mail->addAddress($options['previous_email']);
            }
            $Mail->isHTML(true);

            if ($options['changeEmailOf'] === 'self') {
                $Mail->Subject = __('Email change request');
            } else {
                $Mail->Subject = __('Email changed');
            }

            if (isset($options['admin_email'])) {
                $explodeAdminEmail = explode(',', $options['admin_email']);
                $explodeAdminEmail = array_map('trim', $explodeAdminEmail);
                if (is_array($explodeAdminEmail) && isset($explodeAdminEmail[0])) {
                    $options['admin_email'] = $explodeAdminEmail[0];
                }
                unset($explodeAdminEmail);
            }

            $replaces = [];
            $replaces['%username%'] = $options['user_login'];
            $replaces['%user_login%'] = $options['user_login'];
            $replaces['%newuseremail%'] = $options['user_email'];
            $replaces['%user_email%'] = $options['user_email'];
            $replaces['%contactadminemail%'] = ($options['admin_email'] ?? '');
            $replaces['%changeemailconfirmlink%'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/users/edit?newemail=' . rawurlencode($tokenValue);
            $replaces['%tokenvalue%'] = $tokenValue;
            if ($options['changeEmailOf'] === 'self') {
                $emailMessage = $Email->getMessage('RdbAdmin', 'ChangeEmailConfirmation', $replaces);
            } else {
                $emailMessage = $Email->getMessage('RdbAdmin', 'ChangeEmailNotification', $replaces);
            }
            unset($replaces);
            $Mail->msgHtml($emailMessage, $Email->baseFolder);
            $Mail->AltBody = $Mail->html2text($emailMessage);
            unset($emailMessage);

            $sendResult = $Mail->send();

            if ($sendResult === true) {
                $output['success'] = true;
            } else {
                throw new \Exception('');
            }

            unset($Mail, $sendResult, $tokenValue);
        } catch (\Exception $e) {
            $output['formResultMessage'] = [];
            if ($options['changeEmailOf'] === 'self') {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = '<h4>' . __('Failed to change email.') . '</h4>';
                $output['formResultMessage'][] = '<p>' . __('A confirmation to your new email could not be sent.') . ' ' . $e->getMessage() . '</p>';
            } else {
                $output['formResultStatus'] = 'warning';
                $output['formResultMessage'][] = '<h4>' . __('There is a problem about changing email.') . '</h4>';
                $output['formResultMessage'][] = '<p>' . __('The email was changed but a notification email could not be sent.') . ' ' . $e->getMessage() . '</p>';
            }
            $output['responseStatus'] = 500;
        }// end try..catch

        unset($Email, $Url);

        return $output;
    }// doUpdateSendEmailChanging


    /**
     * Get configuration from DB about user.
     * 
     * @return array
     */
    protected function getConfigDbUser(): array
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configNames = [
            'rdbadmin_UserRegisterDefaultRoles',
            'rdbadmin_UserRegisterNotifyAdminEmails',
            'rdbadmin_UserConfirmEmailChange',
            'rdbadmin_UserConfirmWait',
            'rdbadmin_UserDeleteSelfGrant',
        ];
        $configDefaults = [
            '3',// register default roles
            '',// admin emails
            '0',
            '10',
            '0',
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
     * Edit user page.
     * 
     * @param string $user_id
     * @return string
     */
    public function indexAction($user_id = ''): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if ($user_id !== '' && !$this->isMe($user_id)) {
            $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['configDb'] = array_merge($output['configDb'], $this->getConfigDbUser());

        $output = array_merge($output, $this->getRoles());
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if (trim($user_id) === '' || is_null($user_id)) {
            // if no user_id specified
            // get current user's ID.
            $user_id = (isset($this->userSessionCookieData['user_id']) ? (int) $this->userSessionCookieData['user_id'] : 0);
        } else {
            $user_id = (int) $user_id;
        }

        if ($this->Input->get('newemail')) {
            $updateResult = $this->doConfirmChangeEmail($user_id, $this->Input->get('newemail'));
            if (isset($updateResult['formResultStatus']) && $updateResult['formResultStatus'] === 'success') {
                // if updated successfully.
                $_SESSION['formResult'] = json_encode([($updateResult['formResultStatus'] ?? 'success') => $updateResult['formResultMessage']]);
                unset($updateResult);
                $this->responseNoCache();
                header('Location: ' . $Url->getCurrentUrl(true));
                exit();
            } else {
                // if failed to update.
                if (isset($updateResult['responseStatus'])) {
                    http_response_code($updateResult['responseStatus']);
                } else {
                    http_response_code(403);
                }
                $output = array_merge($output, $updateResult);
            }
            unset($updateResult);
        }

        if (isset($_SESSION['formResult'])) {
            // if there is form result message in the session.
            // display it.
            $formResult = json_decode($_SESSION['formResult'], true);
            if (is_array($formResult)) {
                $output['formResultStatus'] = strip_tags(key($formResult));
                $output['formResultMessage'] = current($formResult);
            }
            unset($formResult, $_SESSION['formResult']);
        }

        $output = array_merge($output, $this->getUserUrlsMethods($user_id));

        // user data for form will be get it via XHR to method GET, URL '/users/{id:\d+}'.
        // pre-define form value.
        $output['user_id'] = $user_id;
        $output['my_user_id'] = (isset($this->userSessionCookieData['user_id']) ? (int) $this->userSessionCookieData['user_id'] : 0);
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $output['predefinedStatusTexts'] = $UsersDb->predefinedStatusText();
        unset($UsersDb);

        $output['pageTitle'] = __('Edit user');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $Url->getAppBasedPath(true) . '/admin',
            ],
            [
                'item' => __('Users'),
                'link' => $Url->getAppBasedPath(true) . '/admin/users',
            ],
            [
                'item' => __('Edit user'),
                'link' => $Url->getAppBasedPath(true) . '/admin/users/edit/' . $user_id,
            ],
        ];

        if ($this->isEditingHigherRole($user_id) === true) {
            http_response_code(403);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
        }

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaUsersEdit'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaUsersEdit', 'rdbaHistoryState'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUsersEdit',
                'RdbaUsers',// must be the same with datatable page because we can use ajax this page in dialog.
                array_merge([
                    'userId' => $user_id,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'txtConfirmDeleteAvatar' => __('Are you sure you want to delete this profile picture?'),
                    'txtConfirmUploadAvatar' => __('Are you sure you want to upload selected profile picture?') . "\n" . __('Any existing profile picture will be changed.'),
                    'txtSelectOnlyOneFile' => __('You can only select one file.'),
                    'txtUploading' => __('Uploading'),
                    'urlAppBased' => $Url->getAppBasedPath(),
                ], $this->getUserUrlsMethods($user_id))
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Users/edit_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
