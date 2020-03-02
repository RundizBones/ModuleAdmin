<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Bulk actions user(s) controller.
 * 
 * @since 0.1
 */
class ActionsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    /**
     * Delete self confirmation page.
     * 
     * @return string
     */
    public function deleteMeAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        // no need permission check

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // set generic values.
        $output = array_merge($output, $this->getUserUrlsMethods());

        $output['user_id'] = (isset($this->userSessionCookieData['user_id']) ? (int) $this->userSessionCookieData['user_id'] : 0);

        $output['pageTitle'] = __('Delete my account');
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
                'link' => $Url->getAppBasedPath(true) . '/admin/users/edit/' . $output['user_id'],
            ],
            [
                'item' => __('Delete my account'),
                'link' => $Url->getCurrentUrl(true),
            ],
        ];

        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        if ($ConfigDb->get('rdbadmin_UserDeleteSelfGrant', 1) == '0') {
            http_response_code(403);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Site setting is not allowed user to delete themself.');
            $output['deleteSelfGrant'] = false;
        }
        unset($ConfigDb);

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
            $Assets->addMultipleAssets('js', ['rdbaUsersDeleteMe'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUsersDeleteMe',
                'RdbaDeleteMe',
                array_merge([
                    'user_id' => $output['user_id'],
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                ], $this->getUserUrlsMethods())
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Users/deleteMe_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// deleteMeAction


    /**
     * Do delete user(s) via REST API.
     * 
     * @param string $user_ids
     * @return string
     */
    public function doDeleteAction($user_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // validate users and action must be select, also verify not editing higher role.
            $output = array_merge($output, $this->validateUsersAction($user_ids, $_DELETE['action']));

            if (isset($output['actionsFormOk']) && $output['actionsFormOk'] === true) {
                // if form validation passed.
                // prepare data for update.
                $data = [];
                $data['user_deleted'] = 1;
                $data['user_deleted_since'] = date('Y-m-d H:i:s');
                $data['user_deleted_since_gmt'] = gmdate('Y-m-d H:i:s');

                $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

                $i = 0;
                if (isset($output['listUsers']['items']) && is_array($output['listUsers']['items'])) {
                    $output['updateResult'] = [];
                    foreach ($output['listUsers']['items'] as $row) {
                        $output['updateResult'][$row->user_id] = $UsersDb->update($data, ['user_id' => $row->user_id]);
                        $i++;
                    }// endforeach;
                    unset($row);
                }

                if ($i > 0) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Deleted successfully.');
                    http_response_code(200);

                    $output['deleted'] = true;
                    $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/users';

                    if (isset($output['formResultMessage'])) {
                        // if there is success message now.
                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                    }
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = __('Unable to delete users.');
                    http_response_code(202);
                }

                unset($data, $UsersDb);
            } else {
                // if form validation failed.
                // it was already send out http response code and message was set, nothing to do here.
            }
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
    }// doDeleteAction


    /**
     * Do delete self account via REST API.
     * 
     * @return string
     */
    public function doDeleteMeAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        // no need to check permission.

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $user_ids = ($this->userSessionCookieData['user_id'] ?? -1);

        // make patch data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // validate users and action must be select, also verify not editing higher role.
            $output = array_merge($output, $this->validateUsersAction($user_ids, $_DELETE['action'], ['checkPermission' => false]));

            if (isset($output['actionsFormOk']) && $output['actionsFormOk'] === true) {
                // if form validation passed.
                // prepare data for update.
                $data = [];
                $data['user_deleted'] = 1;
                $data['user_deleted_since'] = date('Y-m-d H:i:s');
                $data['user_deleted_since_gmt'] = gmdate('Y-m-d H:i:s');

                $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
                $output['updateResult'] = $UsersDb->update($data, ['user_id' => $user_ids]);

                if ($output['updateResult'] === true) {
                    $this->logoutUser([], true);

                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Deleted successfully.');
                    http_response_code(200);

                    $output['deleted'] = true;
                    $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin';
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = __('Unable to delete.');
                    http_response_code(202);
                }

                unset($data, $UsersDb);
            } else {
                // if form validation failed.
                // it was already send out http response code and message was set, nothing to do here.
            }
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
    }// doDeleteMeAction


    /**
     * Do update user(s) via REST API.
     * 
     * @param string $user_ids
     * @return string
     */
    public function doUpdateAction($user_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // validate users and action must be select, also verify not editing higher role.
            $output = array_merge($output, $this->validateUsersAction($user_ids, $_PATCH['action']));

            if (isset($output['actionsFormOk']) && $output['actionsFormOk'] === true) {
                // if form validation passed.
                // prepare data for update.
                $data = [];
                switch ($_PATCH['action']) {
                    case 'enable':
                        $data['user_status'] = 1;
                        $data['user_statustext'] = null;
                        break;
                    case 'disable':
                        $data['user_status'] = 0;
                        break;
                }

                $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);

                $i = 0;
                if (isset($output['listUsers']['items']) && is_array($output['listUsers']['items'])) {
                    $output['updateResult'] = [];
                    foreach ($output['listUsers']['items'] as $row) {
                        $output['updateResult'][$row->user_id] = $UsersDb->update($data, ['user_id' => $row->user_id]);
                        $i++;
                    }// endforeach;
                    unset($row);
                }

                if ($i > 0) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Updated successfully.');
                    http_response_code(200);

                    $output['updated'] = true;
                    $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/users';

                    if (isset($output['formResultMessage'])) {
                        // if there is success message now.
                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                    }
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = __('Unable to update users.');
                    http_response_code(200);
                }

                unset($data, $UsersDb);
            } else {
                // if form validation failed.
                // it was already send out http response code and message was set, nothing to do here.
            }
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
     * Confirmation page for bulk actions.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit', 'delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

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

        // validate users and action must be select, also verify not editing higher role.
        $output = array_merge($output, $this->validateUsersAction($this->Input->get('user_ids'), $this->Input->get('action')));

        // set generic values.
        $output = array_merge($output, $this->getUserUrlsMethods());

        $output['pageTitle'] = __('Confirmation required');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

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
            $Assets->addMultipleAssets('js', ['rdbaUsersActions', 'rdbaHistoryState'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUsersActions',
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
            $output['pageContent'] = $this->Views->render('Admin/Users/actions_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


    /**
     * Validate user(s) and action.
     * 
     * It's validating users and action must be selected.<br>
     * This also validate to make sure that selected users will not have who is higher role.<br>
     * This method was called from `indexAction()`, `doUpdateAction()`, `doDeleteAction()`, `doDeleteMeAction()` methods.
     * 
     * @param string $user_ids The selected user ID(s).
     * @param string $action The selected action.
     * @param array $options The associative array of options. The keys are:<br>
     *                          `checkPermission` (boolean) Set to `true` (default) to check permission.<br>
     * @return array Return associative array with keys:<br>
     *                          `action` The selected action.<br>
     *                          `actionText` The text of selected action, for displaying.<br>
     *                          `user_ids` The selected user IDs.<br>
     *                          `formResultStatus` (optional) If contain any error, it also send out http response code.<br>
     *                          `formResultMessage` (optional) If contain any error, it also send out http response code.<br>
     *                          `listUsers` (optional) The associative array where keys are `total` and `items` of users. Only available if form validation passed.<br>
     *                          `actionsFormOk` The boolean value of form validation. It will be `true` if form validation passed, and will be `false` if it is not.
     */
    protected function validateUsersAction(string $user_ids, string $action, array $options = []): array
    {
        $defaultOptions = [
            'checkPermission' => true,
        ];
        $options = array_merge($defaultOptions, $options);
        unset($defaultOptions);

        $output = [];

        $output['action'] = $action;
        $output['user_ids'] = $user_ids;
        $expUserIds = explode(',', $output['user_ids']);
        $usersActionOk = false;// if users and action were selected, it will be true.

        // validate selected users and action. ------------------------------
        if (count($expUserIds) <= 0) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select at least one user.');
        } else {
            $usersActionOk = true;
        }
        if (empty($output['action'])) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $usersActionOk = false;
        }
        // end validate selected users and action. --------------------------

        // set action text for display.
        if ($output['action'] === 'delete') {
            if ($options['checkPermission'] === true) {
                $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['delete']);
            }
            $output['actionText'] = n__('Delete user', 'Delete users', count($expUserIds));
        } elseif ($output['action'] === 'enable') {
            if ($options['checkPermission'] === true) {
                $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);
            }
            $output['actionText'] = n__('Enable user', 'Enable users', count($expUserIds));
        } elseif ($output['action'] === 'disable') {
            if ($options['checkPermission'] === true) {
                $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);
            }
            $output['actionText'] = n__('Disable user', 'Disable users', count($expUserIds));
        } else {
            $output['actionText'] = $output['action'];

            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $usersActionOk = false;
        }

        $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
        $options = [];
        $options['where']['user_id'] = ($this->userSessionCookieData['user_id'] ?? 0);
        $options['limit'] = 1;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $myRoles = $UsersRolesDb->listItems($options);
        unset($options, $UsersRolesDb);

        if (isset($myRoles['items'])) {
            $myRoles = array_shift($myRoles['items']);

            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
            $listUsers = $UsersDb->listItems(['userIdsIn' => $expUserIds]);

            // validate not editing, deleting higher role. --------------------
            if (isset($listUsers['items']) && is_array($listUsers['items']) && $usersActionOk === true) {
                foreach ($listUsers['items'] as $eachUser) {
                    if ($eachUser->user_id <= 0) {
                        http_response_code(403);
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('Unable to edit guest user.');
                        $usersActionOk = false;
                        break;
                    }

                    if ($ConfigDb->get('rdbadmin_UserDeleteSelfGrant', 1) == '0') {
                        http_response_code(403);
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __('Site setting is not allowed user to delete themself.');
                        $usersActionOk = false;
                        break;
                    }

                    if (isset($eachUser->users_roles) && is_array($eachUser->users_roles)) {
                        foreach ($eachUser->users_roles as $eachRole) {
                            if ($eachRole->userrole_priority < $myRoles->userrole_priority) {
                                http_response_code(403);
                                $output['formResultStatus'] = 'error';
                                $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
                                $usersActionOk = false;
                                break 2;
                            }
                        }// endforeach;
                        unset($eachRole);
                    }
                }// endforeach;
                unset($eachUser);
            }
            // end validate not editing, deleting higher role. ----------------

            $output['listUsers'] = $listUsers;
            unset($ConfigDb, $listUsers, $UsersDb);
        }// endif; $myRoles
        unset($myRoles);

        $output['actionsFormOk'] = $usersActionOk;

        unset($expUserIds, $usersActionOk);

        return $output;
    }// validateUsersAction


}
