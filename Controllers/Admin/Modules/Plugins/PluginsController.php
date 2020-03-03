<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Plugins;


/**
 * Plugins controller.
 * 
 * @since 0.2.4
 */
class PluginsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PluginsTrait;


    /**
     * Get all modules plugins (including enabled and disabled).
     * 
     * @param array $configDb The configuration from config table DB.
     * @return array
     */
    protected function getPlugins(array $configDb): array
    {
        $output = [];

        if ($this->Container->has('Plugins')) {
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');
        } else {
            $Plugins = new \Rdb\Modules\RdbAdmin\Libraries\Plugins($this->Container);
        }
        $options = [];
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        $listPlugins = $Plugins->listPlugins($options);
        unset($options, $Plugins);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = $listPlugins['total'];
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = $listPlugins['items'];

        return $output;
    }// getPlugins


    /**
     * Plugins list page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminModulesPlugins', ['listPlugins', 'managePlugins']);

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

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom accept type or ajax.
            $output = array_merge($output, $this->getPlugins($output['configDb']));
        }

        // set generic values.
        $output = array_merge($output, $this->getPluginUrlsMethods());

        $output['pageTitle'] = __('Modules Plugins');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        $output['permissions'] = [];
        $output['permissions']['listPlugins'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminModulesPlugins', 'listPlugins');
        $output['permissions']['managePlugins'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminModulesPlugins', 'managePlugins');
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

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaModulesPlugins'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaModulesPlugins',
                'RdbaModulesPlugins',
                array_merge(
                    [
                        'isInDataTablesPage' => true,
                        'csrfName' => $output['csrfName'],
                        'csrfValue' => $output['csrfValue'],
                        'csrfKeyPair' => $output['csrfKeyPair'],
                        'permissions' => $output['permissions'],
                        'txtDisabled' => __('Disabled'),
                        'txtEnabled' => __('Enabled'),
                        'txtPleaseSelectAction' => __('Please select an action.'),
                        'txtPleaseSelectAtLeastOnePlugin' => __('Please select at least one plugin.'),
                        'urlAppBased' => $Url->getAppBasedPath(),
                    ], 
                    $this->getPluginUrlsMethods()
                )
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Modules/Plugins/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
