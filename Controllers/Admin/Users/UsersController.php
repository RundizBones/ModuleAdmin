<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Users controller.
 * 
 * @since 0.1
 */
class UsersController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Permissions\Traits\PermissionsTrait;


    /**
     * Get list of **ALL** roles (for display in select box in user management only).
     * 
     * @return array
     */
    protected function doGetRoles(): array
    {
        $output = [];
        $options = [];
        $options['unlimited'] = true;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $output['listRoles'] = $UserRolesDb->listItems($options);

        unset($options, $UserRolesDb);
        return $output;
    }// doGetRoles


    /**
     * Get a user data.
     * 
     * @param string $user_id
     * @return string
     */
    public function doGetUserAction($user_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if ($this->Container->has('UsersSessionsTrait')) {
            if (isset($this->Container['UsersSessionsTrait']->userSessionCookieData['user_id'])) {
                $myUserId = (int) $this->Container['UsersSessionsTrait']->userSessionCookieData['user_id'];
            }
        }

        if (!isset($myUserId) || (isset($myUserId) && $myUserId !== (int) $user_id)) {
            $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['add', 'edit', 'delete', 'list', 'viewLogins']);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        // get a user data.
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $where = [];
        $where['user_id'] = $user_id;
        $options = [];
        $options['getUserFields'] = true;
        $userRow = $UsersDb->get($where, $options);
        unset($options, $UsersDb, $where);

        $output = [];
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $Gravatar = new \Rdb\Modules\RdbAdmin\Libraries\Gravatar();
        $output['gravatarUrl'] = $Gravatar->getImage($userRow->user_email, 200);
        unset($Gravatar);

        if (is_object($userRow) && !empty($userRow)) {
            $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
            $options = [];
            $options['where'] = ['user_id' => (int) $user_id];
            $options['unlimited'] = true;
            $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
            $usersRoles = $UsersRolesDb->listItems($options);
            if (isset($usersRoles['items'])) {
                $userRow->users_roles = $usersRoles['items'];
            } else {
                $userRow->users_roles = [];
            }
            unset($options, $usersRoles, $UsersRolesDb);

            $output['user'] = $userRow;

            $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
            $output['permissions'] = [];
            $output['permissions']['changeRoles'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'changeRoles');
            unset($UserPermissionsDb);
        } else {
            http_response_code(404);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Not found selected user.');
            $output['user'] = null;
            $output['permissions'] = [];
        }
        unset($userRow);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doGetUserAction


    /**
     * Get list of users by conditions (if exists).
     * 
     * @link https://datatables.net/manual/server-side Reference on what to get and what to response.
     * @param array $configDb The configuration from config table DB.
     * @return array Return associative array with users data.
     */
    protected function doGetUsers(array $configDb): array
    {
        $columns = $this->Input->get('columns', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $order = $this->Input->get('order', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $DataTablesJs = new \Rdb\Modules\RdbAdmin\Libraries\DataTablesJs();
        $sortOrders = $DataTablesJs->buildSortOrdersFromInput($columns, $order);
        unset($columns, $DataTablesJs, $order);

        $output = [];

        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $options = [];
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        if (
            (
                isset($_GET['filterStatus']) && trim($_GET['filterStatus']) != ''
            ) || 
            (
                isset($_GET['filterRole']) && trim($_GET['filterRole']) != ''
            )
        ) {
            $options['where'] = [];
            if (isset($_GET['filterStatus']) && trim($_GET['filterStatus']) != '') {
                $options['where']['user_status'] = $this->Input->get('filterStatus', 1, FILTER_SANITIZE_NUMBER_INT);
            }
            if (isset($_GET['filterRole']) && trim($_GET['filterRole']) != '') {
                $options['where']['users_roles.userrole_id'] = $this->Input->get('filterRole', 1, FILTER_SANITIZE_NUMBER_INT);
            }
        }
        $result = $UsersDb->listItems($options);
        unset($options, $sortOrders, $UsersDb);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetUsers


    /**
     * Users list page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['add', 'edit', 'delete', 'list', 'viewLogins']);

        if (session_status() === PHP_SESSION_NONE) {
            session_cache_limiter('private_no_expire');
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if ($this->Input->isNonHtmlAccept()) {
            $output = array_merge($output, $this->doGetUsers($output['configDb']));
            $output = array_merge($output, $this->doGetRoles());

            if (isset($_SESSION['formResult'])) {
                $formResult = json_decode($_SESSION['formResult'], true);
                if (is_array($formResult)) {
                    $output['formResultStatus'] = strip_tags(key($formResult));
                    $output['formResultMessage'] = current($formResult);
                }
                unset($formResult, $_SESSION['formResult']);
            }
        }

        // set generic values.
        $output = array_merge($output, $this->getUserUrlsMethods());

        $output['pageTitle'] = __('Manage users');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        $output['permissions'] = [];
        $output['permissions']['add'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'add');
        $output['permissions']['edit'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'edit');
        $output['permissions']['delete'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'delete');
        $output['permissions']['viewLogins'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'viewLogins');
        $output['permissions']['changeRoles'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'changeRoles');
        $output['permissions']['managePermissions'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminPermissions', 'managePermissions');
        unset($UserPermissionsDb);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaUsers'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUsers',
                'RdbaUsers',
                array_merge(
                    [
                        'isInDataTablesPage' => true,
                        'csrfName' => $output['csrfName'],
                        'csrfValue' => $output['csrfValue'],
                        'csrfKeyPair' => $output['csrfKeyPair'],
                        'permissions' => $output['permissions'],
                        'txtConfirmDeleteAvatar' => __('Are you sure you want to delete this profile picture?'),
                        'txtConfirmUploadAvatar' => __('Are you sure you want to upload selected profile picture?') . "\n" . __('Any existing profile picture will be changed.'),
                        'txtDisabled' => __('Disabled'),
                        'txtEnabled' => __('Enabled'),
                        'txtPleaseSelectAction' => __('Please select an action.'),
                        'txtPleaseSelectAtLeastOneUser' => __('Please select at least one user.'),
                        'txtSelectOnlyOneFile' => __('You can only select one file.'),
                        'txtUnknow' => __('Unknow'),
                        'txtUploading' => __('Uploading'),
                        'urlAppBased' => $Url->getAppBasedPath(),
                    ], 
                    $this->getUserUrlsMethods(),
                    $this->getPermissionUrlsMethods()
                )
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Users/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
