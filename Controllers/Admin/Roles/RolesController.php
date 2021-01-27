<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Roles;


/**
 * Roles controller.
 * 
 * @since 0.1
 */
class RolesController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\RolesTrait;


    /**
     * Get a single role data.
     * 
     * @param string $userrole_id
     * @return string
     */
    public function doGetRoleAction($userrole_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['add', 'edit', 'delete', 'list', 'changePriority']);

        $this->Languages->getHelpers();

        // get a role data.
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $where = [];
        $where['userrole_id'] = $userrole_id;
        $roleRow = $UserRolesDb->get($where);
        unset($UserRolesDb, $where);

        $output = [];
        if (is_object($roleRow) && !empty($roleRow)) {
            // if found role.
            $output['role'] = $roleRow;
        } else {
            // if not found.
            http_response_code(404);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Not found selected role.');
            $output['role'] = null;
        }
        unset($roleRow);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doGetRoleAction


    /**
     * Get list of **ALL** roles.
     * 
     * @return array
     */
    protected function doGetRoles(): array
    {
        $output = [];

        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $options = [];
        $options['unlimited'] = true;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        $result = $UserRolesDb->listItems($options);
        unset($options);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);
        $output['restrictedPriority'] = $UserRolesDb->restrictedPriority;

        unset($UserRolesDb);

        // populate additional data.
        if (is_array($output['listItems'])) {
            foreach ($output['listItems'] as $row) {
                if (in_array((int) $row->userrole_priority, $output['restrictedPriority'])) {
                    $row->restrictedPriority = true;
                } else {
                    $row->restrictedPriority = false;
                }
            }// endforeach;
            unset($row);
        }

        return $output;
    }// doGetRoles


    /**
     * Roles management page and get roles data via REST API.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['add', 'edit', 'delete', 'list', 'changePriority']);

        if (session_id() === '') {
            session_cache_limiter('private_no_expire');
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if ($this->Input->isNonHtmlAccept()) {
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

        // set URLs & methods.
        $output['urls'] = $this->getRoleUrlsMethods();

        $output['pageTitle'] = __('Manage roles');
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
        ];

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $MyModuleAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdbaRoles'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaRoles',
                'RdbaRoles',
                array_merge([
                    'isInDataTablesPage' => true,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOneRole' => __('Please select at least one role.'),
                ], $this->getRoleUrlsMethods())
            );

            $this->setCssAssets($Assets, $MyModuleAssets);
            $this->setJsAssetsAndObject($Assets, $MyModuleAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Roles/index_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
