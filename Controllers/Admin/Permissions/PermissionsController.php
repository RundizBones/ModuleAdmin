<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Permissions;


/**
 * Permissions controller.
 * 
 * @since 0.1
 */
class PermissionsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PermissionsTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Roles\Traits\RolesTrait;


    /**
     * Get permissions data.
     * 
     * Get permission pages, actions, checked data for specific module and roles or user.
     * 
     * @param string $permissionFor Query string that this management is get permission for 'roles' or 'users'.
     * @param int $user_id If getting permission for 'users', please specify user ID.
     * @param string $permissionModule The module to get checked values.
     * @param array $listColumns List columns get from `getRoles()`, or `getUser()` method. The array must contain 'items' key in it.
     * @return array Return associative array.
     */
    protected function getPermissionsData(
        string $permissionFor = 'roles', 
        int $user_id = 0, 
        string $permissionModule = '',
        array $listColumns = []
    ): array
    {
        if ($this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
        } else {
            $Modules = new \Rdb\System\Modules($this->Container);
            $Modules->setCurrentModule(get_called_class());
        }

        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];

        // check to make sure that permissionModule value was set and not empty.
        if ($permissionModule === '') {
            $permissionModule = $Modules->getModuleSystemName(__DIR__);
            $output['permissionModule'] = $permissionModule;
        }

        // list enabled modules.
        $output['listModules'] = $Modules->getModules();

        unset($Modules);

        // get module's pages and actions.
        if (is_file(MODULE_PATH . '/' . $permissionModule . '/ModuleData/ModuleAdmin.php')) {
            $ModuleAdminInterfaceInstance = new \ReflectionClass('\\Rdb\\Modules\\RdbAdmin\\Interfaces\\ModuleAdmin');
            $ReflectionClass = new \ReflectionClass('\\Rdb\\Modules\\' . $permissionModule . '\\ModuleData\\ModuleAdmin');
            $ModuleAdminClass = $ReflectionClass->newInstanceWithoutConstructor();

            if ($ModuleAdminInterfaceInstance->isInstance($ModuleAdminClass)) {
                // if module admin class in instance of module admin interface.
                // get defined permissions for selected module.
                /* @var $ModuleAdmin \Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin */
                $ModuleAdmin = $ReflectionClass->newInstance($this->Container);
                $permissions = $ModuleAdmin->definePermissions();

                // get permissions saved in DB.
                $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
                $options = [];
                $options['unlimited'] = true;
                $options['where'] = [];
                $options['where']['module_system_name'] = $permissionModule;
                if ($permissionFor === 'users') {
                    $options['where']['user_permissions.user_id'] = $user_id;
                }
                $permissionDb = $UserPermissionsDb->listItems($options);
                unset($options, $UserPermissionsDb);
            }

            unset($ModuleAdminClass, $ModuleAdminInterfaceInstance, $ReflectionClass);
        }

        $output['listColumns'] = [];
        $output['listColumns'][0] = new \stdClass();
        $output['listColumns'][0]->name = __('Page');
        $output['listColumns'][1] = new \stdClass();
        $output['listColumns'][1]->name = __('Action');

        // populate columns for use in datatables.
        if (array_key_exists('items', $listColumns) && is_array($listColumns['items'])) {
            $columnArrayKey = 2;
            foreach ($listColumns['items'] as $column) {
                if (isset($column->userrole_name)) {
                    $columnName = $column->userrole_name;
                } elseif (isset($column->user_login)) {
                    $columnName = $column->user_login;
                } else {
                    $columnName = '';
                }

                if ($permissionFor === 'roles') {
                    $roleUrls = $this->getRoleUrlsMethods(($column->userrole_id ?? ''));
                    $editLink = ($roleUrls['editRolePageUrl'] ?? $Url->getAppBasedPath(true) . '/admin/roles/edit/' . ($column->userrole_id ?? ''));
                    unset($roleUrls);
                } elseif ($permissionFor === 'users') {
                    $userUrls = $this->getUserUrlsMethods($user_id);
                    $editLink = ($userUrls['editUserUrl'] ?? $Url->getAppBasedPath(true) . '/admin/users/edit/' . $user_id);
                    unset($userUrls);
                } else {
                    $editLink = '';
                }

                $Column = new \stdClass();
                // @link https://datatables.net/reference/option/columns Reference.
                $Column->name = $columnName;
                if (!empty($editLink)) {
                    $Column->editLink = $editLink;
                }
                $output['listColumns'][$columnArrayKey] = $Column;
                unset($Column, $columnName, $editLink);

                $columnArrayKey++;
            }// endforeach;
            unset($column, $columnArrayKey);
        }

        // populate the results.
        $tableRows = [];
        $totalRows = 0;
        if (isset($permissions) && is_array($permissions)) {
            foreach ($permissions as $permissionPage => $permissionActions) {
                $countAction = 1;
                foreach ($permissionActions as $permissionAction) {
                    $tableRows[$totalRows] = [];
                    if ($countAction === 1) {
                        $tableRows[$totalRows][0] = new \stdClass();
                        $tableRows[$totalRows][0]->name = $ModuleAdmin->permissionDisplayText($permissionPage, true);
                        $tableRows[$totalRows][0]->totalActions = count($permissionActions);
                        $tableRows[$totalRows][0]->type = 'display';
                    } else {
                        $tableRows[$totalRows][0] = new \stdClass();
                    }
                    $tableRows[$totalRows][1] = new \stdClass();
                    $tableRows[$totalRows][1]->name = $ModuleAdmin->permissionDisplayText($permissionAction, true);
                    $tableRows[$totalRows][1]->type = 'display';
                    $columnArrayKey = 2;

                    if (array_key_exists('items', $listColumns) && is_array($listColumns['items'])) {
                        foreach ($listColumns['items'] as $column) {
                            if (isset($column->userrole_id)) {
                                $idValue = $column->userrole_id;
                            } elseif (isset($column->user_id)) {
                                $idValue = $column->user_id;
                            } else {
                                $idValue = '';
                            }

                            if ($permissionFor === 'roles') {
                                $idName = 'userrole_id';
                            } elseif ($permissionFor === 'users') {
                                $idName = 'user_id';
                            } else {
                                $idName = '';
                            }

                            if (isset($column->userrole_priority) && $column->userrole_priority == '1') {
                                // if highest priority role.
                                $checked = true;
                                $alwaysChecked = true;
                            } else {
                                $checked = false;
                                // determine checked from role priority of selected user.
                                if (isset($column->users_roles) && is_array($column->users_roles)) {
                                    foreach ($column->users_roles as $usersRole) {
                                        if (isset($usersRole->userrole_priority) && $usersRole->userrole_priority == '1') {
                                            // if role priority of this user is highest.
                                            $checked = true;
                                            $alwaysChecked = true;
                                        }
                                    }// endforeach;
                                    unset($usersRole);
                                }

                                // determine checked from saved data in db.
                                if (
                                    $checked === false && 
                                    isset($permissionDb) && 
                                    is_array($permissionDb) && 
                                    array_key_exists('items', $permissionDb) && 
                                    is_array($permissionDb['items'])
                                ) {
                                    foreach ($permissionDb['items'] as $permissionRow) {
                                        if (
                                            $idValue !== '' &&
                                            $permissionRow->{$idName} === $idValue &&
                                            $permissionRow->module_system_name === $permissionModule &&
                                            $permissionRow->permission_page === $permissionPage &&
                                            $permissionRow->permission_action === $permissionAction
                                        ) {
                                            $checked = true;
                                        }
                                    }// endforeach;
                                    unset($permissionRow);
                                }
                            }

                            $tableRows[$totalRows][$columnArrayKey] = new \stdClass();
                            $tableRows[$totalRows][$columnArrayKey]->type = 'checkbox';
                            $tableRows[$totalRows][$columnArrayKey]->checked = $checked;
                            if (isset($alwaysChecked) && $alwaysChecked === true) {
                                // if highest priority role.
                                $tableRows[$totalRows][$columnArrayKey]->alwaysChecked = true;
                            }
                            $tableRows[$totalRows][$columnArrayKey]->identityName = $idName;
                            $tableRows[$totalRows][$columnArrayKey]->identityValue = $idValue;
                            $tableRows[$totalRows][$columnArrayKey]->permissionPageData = $permissionPage;
                            $tableRows[$totalRows][$columnArrayKey]->permissionActionData = $permissionAction;

                            $columnArrayKey++;
                            unset($alwaysChecked, $checked, $idName, $idValue);
                        }// endforeach;
                        unset($column);
                    }

                    unset($columnArrayKey);
                    $countAction++;
                    $totalRows++;
                }// endforeach;
                unset($countAction, $permissionAction);
            }// endforeach;
            unset($permissionActions, $permissionPage);
        }
        unset($ModuleAdmin, $permissionDb, $permissions, $totalRows);

        // output the result for listing in table.
        $output['listItems'] = $tableRows;

        unset($tableRows, $Url);

        return $output;
    }// getPermissionsData


    /**
     * Get list of **ALL** roles.
     * 
     * @return array
     */
    protected function getRoles(): array
    {
        $output = [];
        $options = [];
        $options['unlimited'] = true;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $output['listColumns'] = $UserRolesDb->listItems($options);

        unset($options, $UserRolesDb);
        return $output;
    }// getRoles


    /**
     * Get a user data.
     * 
     * @param int $user_id
     * @return array
     */
    protected function getUser(int $user_id): array
    {
        $output = [];
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $where = [];
        $where['user_id'] = $user_id;
        $options = [];
        $options['getUserFields'] = false;
        $userRow = $UsersDb->get($where, $options);
        unset($options, $UsersDb, $where);

        if (is_object($userRow) && !empty($userRow)) {
            // if successfully get user data.
            // get user's roles data.
            $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
            $options = [];
            $options['where']['user_id'] = $user_id;
            $options['unlimited'] = true;
            $userRoles = $UsersRolesDb->listItems($options);
            unset($options, $UsersRolesDb);

            if (isset($userRoles['items']) && !empty($userRoles['items'])) {
                $userRow->users_roles = $userRoles['items'];
            } else {
                $userRow->users_roles = [];
            }
            unset($userRoles);

            // remove some fields to keep privacy.
            unset(
                $userRow->user_lastlogin, 
                $userRow->user_lastlogin_gmt, 
                $userRow->user_status, 
                $userRow->user_statustext,
                $userRow->user_deleted,
                $userRow->user_deleted_since,
                $userRow->user_deleted_since_gmt
            );

            $output['listColumns']['total'] = count((array) $userRow);
            $output['listColumns']['items'] = [$userRow];
            $output['userData'] = $userRow;
        } else {
            $output['listColumns']['total'] = 0;
            $output['listColumns']['items'] = [];
        }
        unset($userRow);

        return $output;
    }// getUser


    /**
     * List permissions page and get data via REST API.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminPermissions', ['managePermissions']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);// use persistent for ajax save on click can be multiple click  at a time.
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // get query string that this page is managing permission for (roles, users) and module (which module - use folder name).
        $output['permissionFor'] = $this->Input->get('permissionFor', 'roles');
        if ($output['permissionFor'] !== 'roles' && $output['permissionFor'] !== 'users') {
            $output['permissionFor'] = 'roles';
        }
        $output['permissionForUserId'] = (int) $this->Input->get('permissionForUserId', 0);
        $output['permissionModule'] = $this->Input->get('permissionModule');


        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content, ajax.
            // get names of roles or a user (depend on permissionFor).
            if ($output['permissionFor'] === 'roles') {
                // if managing permission for roles.
                // get roles
                $output = array_merge($output, $this->getRoles());
            } else {
                // if managing permission for users.
                // get user
                $output = array_merge($output, $this->getUser($output['permissionForUserId']));
            }

            // get permission data.
            $output = array_merge(
                $output, 
                $this->getPermissionsData(
                    $output['permissionFor'],
                    $output['permissionForUserId'],
                    $output['permissionModule'],
                    $output['listColumns']
                )
            );
        }

        if ($output['permissionModule'] === '') {
            $output['permissionModule'] = 'RdbAdmin';// current (this) module.
        }

        // set urls and mothods
        $output['urls'] = $this->getPermissionUrlsMethods();

        $output['pageTitle'] = __('Manage permissions');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $Url->getAppBasedPath(true) . '/admin',
            ],
            [
                'item' => __('Manage permissions'),
                'link' => $Url->getAppBasedPath(true) . '/admin/permissions',
            ],
        ];

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content, ajax.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaPermissions'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaPermissions',
                'RdbaPermissions',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'permissionFor' => $output['permissionFor'],
                    'permissionForUserId' => $output['permissionForUserId'],
                    'permissionModule' => $output['permissionModule'],
                    'txtAreYouSureClear' => sprintf(__('Are you sure to clear all permissions for selected module (%s)?'), $output['permissionModule']) . 
                        "\n" . 
                        __('This cannot be undone, any users that are in the highest role priority will always have full control.'),
                    'txtLoading' => __('Loading &hellip;'),
                    'txtNoData' => __('No data'),
                    'txtPermissionThisRoleCantChange' => __('Permissions for this role cannot be changed.'),
                ], 
                    $this->getPermissionUrlsMethods(),
                    $this->getUserUrlsMethods()
                )
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Permissions/index_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
