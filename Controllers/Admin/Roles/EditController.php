<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Roles;


/**
 * Edit role controller.
 * 
 * @since 0.1
 */
class EditController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\RolesTrait;


    /**
     * Do update role via REST API.
     * 
     * @param string $userrole_id
     * @return string
     */
    public function doUpdateAction($userrole_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['edit']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            $userrole_id = (int) $userrole_id;
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);
            
            // prepare data for checking.
            $data = [];
            $data['userrole_name'] = htmlspecialchars(trim($this->Input->patch('userrole_name')), ENT_QUOTES);
            if (isset($_PATCH['userrole_description'])) {
                $data['userrole_description'] = htmlspecialchars(trim($this->Input->patch('userrole_description')), ENT_QUOTES);
                if (empty($data['userrole_description'])) {
                    $data['userrole_description'] = null;
                }
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
                // update to DB.
                $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
                $updateResult = $UserRolesDb->update($data, ['userrole_id' => $userrole_id]);
                $output['rowUpdated'] = $this->Db->PDOStatement()->rowCount();
                unset($UserRolesDb);

                if ($updateResult === true && $output['rowUpdated'] >= 1) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Role updated');
                    http_response_code(200);
                    $output['redirectBack'] = $Url->getAppBasedPath(true) . '/admin/roles';

                    $_SESSION['formResult'] = json_encode([$output['formResultStatus'] => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Unable to update role, please contact administrator.');
                    http_response_code(400);
                }
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
     * Edit role page.
     * 
     * @param string $userrole_id
     * @return string
     */
    public function indexAction($userrole_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['edit']);

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
        $output['urls'] = $this->getRoleUrlsMethods($userrole_id);

        $output['userrole_id'] = $userrole_id;
        $output['pageTitle'] = __('Edit role');
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
                'item' => __('Edit role'),
                'link' => $Url->getAppBasedPath(true) . '/admin/roles/edit/' . $userrole_id,
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
            $Assets->addMultipleAssets('js', ['rdbaRolesEdit', 'rdbaHistoryState'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaRolesEdit',
                'RdbaRoles',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                ], $this->getRoleUrlsMethods($userrole_id))
            );

            $this->setCssAssets($Assets, $MyModuleAssets);
            $this->setJsAssetsAndObject($Assets, $MyModuleAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['pageContent'] = $this->Views->render('Admin/Roles/edit_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $MyModuleAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
