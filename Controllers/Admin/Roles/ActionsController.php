<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Roles;


/**
 * Bulk actions roles controller.
 * 
 * @since 0.1
 */
class ActionsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\RolesTrait;


    /**
     * Delete roles via REST API.
     * 
     * @global array $_DELETE
     * @param string $userrole_ids
     * @return string
     */
    public function doDeleteAction(string $userrole_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['delete']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (!isset($_DELETE['action'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // prepare form data
            $data = [];
            $data['config_rdbadmin_UserRegisterDefaultRoles'] = $this->Input->delete('config_rdbadmin_UserRegisterDefaultRoles');
            $data['new_usersroles_id'] = $this->Input->delete('new_usersroles_id');

            // validate roles and action must be select, also verify not editing higher role.
            $validateRolesAction = $this->validateRoleActions($userrole_ids, $_DELETE['action']);

            if (isset($validateRolesAction['formValidated']) && $validateRolesAction['formValidated'] === true) {
                // if form validation passed.
                $formValidated = true;

                if (
                    isset($validateRolesAction['deleteUserDefaultRole']) && 
                    $validateRolesAction['deleteUserDefaultRole'] === true &&
                    empty($data['config_rdbadmin_UserRegisterDefaultRoles'])
                ) {
                    $formValidated = false;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Please select user\'s default role.');
                    http_response_code(400);
                }

                if ($formValidated === true && empty($data['new_usersroles_id'])) {
                    $formValidated = false;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Please select replace user\'s role.');
                    http_response_code(400);
                }
            } else {
                // if form validation failed.
                if (isset($validateRolesAction['formResultStatus']) && isset($validateRolesAction['formResultMessage'])) {
                    $output['formResultStatus'] = $validateRolesAction['formResultStatus'];
                    $output['formResultMessage'] = $validateRolesAction['formResultMessage'];
                }
            }

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                if (
                    isset($validateRolesAction['deleteUserDefaultRole']) && 
                    $validateRolesAction['deleteUserDefaultRole'] === true
                ) {
                    // if deleting user default role.
                    // update configDb user default role. -----------------------------------
                    $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                    $defaultRoles = $ConfigDb->get('rdbadmin_UserRegisterDefaultRoles');
                    $defaultRoleArray = explode(',', $defaultRoles);
                    unset($defaultRoles);

                    if (is_array($defaultRoleArray)) {
                        $defaultRoleArray = array_map('trim', $defaultRoleArray);
                        foreach ($defaultRoleArray as $key => $defaultRole) {
                            if (in_array($defaultRole, $validateRolesAction['userrole_id_array'])) {
                                unset($defaultRoleArray[$key]);
                            }
                        }// endforeach;
                        unset($defaultRole, $key);

                        $defaultRoleArray[] = $data['config_rdbadmin_UserRegisterDefaultRoles'];
                        $defaultRoleArray = array_unique($defaultRoleArray);
                        $defaultRoleValue = implode(',', $defaultRoleArray);
                        if ($defaultRoleValue !== '') {
                            $ConfigDb->updateValue($defaultRoleValue, 'rdbadmin_UserRegisterDefaultRoles');
                        }
                        unset($defaultRoleValue);
                    }
                    unset($ConfigDb, $defaultRoleArray, $defaultRoles);
                }

                // replace users's role. --------------------------------------------------------
                $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
                $optoins = [];
                $options['roleIdsIn'] = $validateRolesAction['userrole_id_array'];
                $listUsersRoles = $UsersRolesDb->listItems($options);// get the users that has deleting role ids.
                unset($options);

                if (isset($listUsersRoles['items']) && is_array($listUsersRoles['items'])) {
                    $output['updateUsersRoles'] = [];
                    foreach ($listUsersRoles['items'] as $row) {
                        $optionPerUser = [];
                        $optionPerUser['where'] = [
                            'user_id' => $row->user_id,
                            'users_roles.userrole_id' => $data['new_usersroles_id'],
                        ];
                        $listUserRoles = $UsersRolesDb->listItems($optionPerUser);// get role ids for this user that already has new replacement role id.
                        if (!isset($listUserRoles['total']) || $listUserRoles['total'] <= 0) {
                            // if not found that this user already has new replacement role id.
                            if ($UsersRolesDb->add((int) $row->user_id, [$data['new_usersroles_id']]) === true) {
                                // if success add replacement role id.
                                $output['updateUsersRoles'][$row->user_id][] = $data['new_usersroles_id'];
                            }
                        }
                        unset($listUserRoles);
                    }// endforeach;
                    unset($row);
                }
                unset($listUsersRoles, $UsersRolesDb);

                // now, delete roles from user_roles and users_roles tables. -----------
                $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
                $output['deleted'] = $UserRolesDb->delete($validateRolesAction['userrole_id_array']);
                unset($UserRolesDb);

                if ($output['deleted'] === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Deleted successfully.');
                    http_response_code(200);

                    $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/users';

                    if (isset($output['formResultMessage'])) {
                        // if there is success message now.
                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                    }
                }
            }// endif; form validation passed.

            unset($data, $validateRolesAction);
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
     * Display bulk actions page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['delete']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // validate roles and action must be selected.
        $output = array_merge($output, $this->validateRoleActions($this->Input->get('userrole_ids'), $this->Input->get('action')));

        // list selected roles.
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $options = [];
        $options['unlimited'] = true;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $options['where'] = [
            'userrole_priority' => '< 10000',
        ];
        $output['listRoles'] = $UserRolesDb->listItems($options);
        unset($options);
        unset($UserRolesDb);

        // set URLs & methods.
        $output['urls'] = $this->getRoleUrlsMethods();

        $output['pageTitle'] = __('Confirmation required');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $Url->getAppBasedPath(true) . '/admin',
            ],
            [
                'item' => __('Manage roles'),
                'link' => $Url->getAppBasedPath(true) . '/admin/roles',
            ],
            [
                'item' => __('Confirmation required'),
                'link' => $Url->getAppBasedPath(true) . '/admin/roles/actions' . $Url->getQuerystring(),
            ],
        ];

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $MyModuleAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdbaRolesActions', 'rdbaHistoryState'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaRolesActions',
                'RdbaRoles',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                ], $this->getRoleUrlsMethods())
            );

            $this->setCssAssets($Assets, $MyModuleAssets);
            $this->setJsAssetsAndObject($Assets, $MyModuleAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Roles/actions_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


    /**
     * Validate role(s) and action.
     * 
     * It's validating roles and action must be selected.<br>
     * Check that selected role's priority is not in restricted roles.<br>
     * Check that deleting user default role on register. (no error message just return 'deleteUserDefaultRole' array key in boolean).<br>
     * This method set http response code if contain errors.<br>
     * This method was called from `indexAction()`, `doDeleteAction()` methods.
     * 
     * @param string $userrole_ids The selected role ID(s).
     * @param string $action The selected action.
     * @return array Return associative array with keys:<br>
     *                          `action` The selected action.<br>
     *                          `actionText` The text of selected action, for displaying.<br>
     *                          `userrole_ids` The selected role IDs.<br>
     *                          `userrole_id_array` The selected role IDs as array.<br>
     *                          `formResultStatus` (optional) If contain any error, it also send out http response code.<br>
     *                          `formResultMessage` (optional) If contain any error, it also send out http response code.<br>
     *                          `formValidated` The boolean value of form validation. It will be `true` if form validation passed, and will be `false` if it is not.<br>
     *                          `deleteUserDefaultRole` (optional) If deleting user default roles then it will be `true`.<br>
     *                          `deleteUserDefaultRoleIds` (optional) If deleting user default roles, this will contain the ids.
     */
    protected function validateRoleActions(string $userrole_ids, string $action): array
    {
        $output = [];

        $output['action'] = $action;
        $output['userrole_ids'] = $userrole_ids;
        $expUserRoleIds = explode(',', $output['userrole_ids']);

        if (is_array($expUserRoleIds)) {
            $output['userrole_id_array'] = $expUserRoleIds;
            $totalSelectedRoles = (int) count($expUserRoleIds);
        } else {
            $output['userrole_id_array'] = [];
            $totalSelectedRoles = 0;
        }
        unset($expUserRoleIds);

        $formValidated = false;

        // validate selected usersand action. ------------------------------
        if ($totalSelectedRoles <= 0) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select at least one role.');
        } else {
            $formValidated = true;
        }
        if (empty($output['action'])) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $formValidated = false;
        }
        // end validate selected usersand action. --------------------------

        // set action text for display.
        if ($output['action'] === 'delete') {
            $output['actionText'] = n__('Delete role', 'Delete roles', $totalSelectedRoles);
        } else {
            $output['actionText'] = $output['action'];
        }

        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);

        // get selected user roles data.
        $options = [];
        $options['roleIdsIn'] = $output['userrole_id_array'];
        $options['unlimited'] = true;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $output['listSelectedRoles'] = $UserRolesDb->listItems($options);
        unset($options);

        if ($formValidated === true) {
            if ($output['action'] === 'delete') {
                $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                $userRegisterDefaultRoles = $ConfigDb->get('rdbadmin_UserRegisterDefaultRoles');
                $output['deleteUserDefaultRole'] = false;
                $output['deleteUserDefaultRoleIds'] = [];

                // check that deleting user default roles.
                if (is_scalar($userRegisterDefaultRoles) && !empty($userRegisterDefaultRoles)) {
                    $userRegisterDefaultRolesArray = explode(',', $userRegisterDefaultRoles);
                    if (is_array($userRegisterDefaultRolesArray)) {
                        $userRegisterDefaultRolesArray = array_map('trim', $userRegisterDefaultRolesArray);
                        foreach ($userRegisterDefaultRolesArray as $eachDefaultRole) {
                            if (in_array($eachDefaultRole, $output['userrole_id_array'])) {
                                $output['deleteUserDefaultRole'] = true;
                                $output['deleteUserDefaultRoleIds'][] = $eachDefaultRole;
                            }
                        }// endforeach;
                        unset($eachDefaultRole);
                    }
                    unset($userRegisterDefaultRolesArray);
                }

                unset($ConfigDb, $userRegisterDefaultRoles);

                // check selected roles has restricted priorities.
                if (isset($output['listSelectedRoles']['items']) && is_array($output['listSelectedRoles']['items'])) {
                    foreach ($output['listSelectedRoles']['items'] as $row) {
                        if (in_array($row->userrole_priority, $UserRolesDb->restrictedPriority)) {
                            http_response_code(400);
                            $output['formResultStatus'] = 'error';
                            $output['formResultMessage'][] = __('Unable to delete restricted roles.');
                            $formValidated = false;
                            break;
                        }
                    }// endforeach;
                    unset($row);
                }
            }// endif; action.
        }// endif form validated.

        unset($UserRolesDb);

        $output['formValidated'] = $formValidated;

        unset($formValidated, $totalSelectedRoles);

        return $output;
    }// validateRoleActions


}
