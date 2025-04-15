<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Roles;


/**
 * Add role controller.
 * 
 * @since 0.1
 */
class AddController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\RolesTrait;


    /**
     * Do add role via REST API.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['add']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

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
            $data['userrole_name'] = htmlspecialchars(trim($this->Input->post('userrole_name')), ENT_QUOTES);
            $data['userrole_description'] = htmlspecialchars(trim($this->Input->post('userrole_description')), ENT_QUOTES);

            if (empty($data['userrole_description'])) {
                $data['userrole_description'] = null;
            }

            // validate the form. --------------------------------------------------------
            $formValidated = true;
            if (empty($data['userrole_name'])) {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter role name.');
                http_response_code(400);
            }

            if ($formValidated === true) {
                // if form validation passed.
                // insert role to DB.
                $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
                $userroleId = $UserRolesDb->add($data);

                if ($userroleId !== false && $userroleId > '0') {
                    // if added success.
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('New role created.') . ' <a class="rdba-listpage-edit" href="' . $Url->getAppBasedPath(true) . '/admin/roles/edit/' . $userroleId . '">' . __('Edit role') . '</a>';
                    $output['userrole_id'] = $userroleId;
                    http_response_code(201);

                    $output['redirectBack'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true) . '/admin/roles';

                    if (isset($output['formResultMessage'])) {
                        // if there is success message now.
                        // set to session to redirect back to listing page and show message there.
                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                    }
                } else {
                    // if added failed.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Unable to add new role, please contact administrator.');
                    http_response_code(400);
                }

                unset($UserRolesDb);
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
    }// doAddAction


    /**
     * Add role page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['add']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // set URLs & methods.
        $output['urls'] = $this->getRoleUrlsMethods();

        $output['pageTitle'] = __('Add new role');
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
                'item' => __('Add new role'),
                'link' => $Url->getAppBasedPath(true) . '/admin/roles/add/',
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
            $Assets->addMultipleAssets('js', ['rdbaRolesAdd', 'rdbaHistoryState'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaRolesAdd',
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
            $output['pageContent'] = $this->Views->render('Admin/Roles/add_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
