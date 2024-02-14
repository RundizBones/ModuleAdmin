<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules;


/**
 * Modules management controller.
 * 
 * @since 1.2.5
 */
class ModulesController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\ModulesTrait;


    /**
     * Get all modules (including enabled and disabled).
     * 
     * @param array $configDb The configuration from config table DB.
     * @return array
     */
    protected function getModules(array $configDb): array
    {
        $output = [];

        if ($this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
        } else {
            $Modules = new \Rdb\System\Modules($this->Container);
        }
        $output['currentModule'] = $Modules->getCurrentModule();
        unset($Modules);

        $ModulesClass = new \Rdb\Modules\RdbAdmin\Libraries\Modules($this->Container);
        $listModules = $ModulesClass->listModules();
        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = $listModules['total'];
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = $listModules['items'];
        unset($listModules, $ModulesClass);

        return $output;
    }// getModules


    /**
     * Modules list page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminModules', ['list', 'manageModules']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom accept type or ajax.
            $output = array_merge($output, $this->getModules($output['configDb']));
        }

        // set generic values.
        $output['urls'] = $this->getModuleUrlsMethods();

        $output['pageTitle'] = __('Modules');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        $output['permissions'] = [];
        $output['permissions']['list'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminModules', 'list');
        $output['permissions']['manageModules'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminModules', 'manageModules');
        unset($UserPermissionsDb);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content, or ajax.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage', 'rdbaModules'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaModules'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaModules',
                'RdbaModulesObject',
                [
                    'isInDataTablesPage' => true,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'permissions' => $output['permissions'],
                    'txtConfirmUpdateModule' => sprintf(
                        __('This will run update module with the same as command %1$s.'),
                        '`php rdb system:module update`'
                    ) . "\n" .
                        __('Are you sure?'),
                    'txtDisabled' => __('Disabled'),
                    'txtEnabled' => __('Enabled'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOneModule' => __('Please select at least one module.'),
                    'txtPleaseSelectOneModule' => __('Please select only one module.'),
                    'urlAppBased' => $Url->getAppBasedPath(),
                    'urls' => $output['urls'],
                ]
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Modules/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
