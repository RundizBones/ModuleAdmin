<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Controllers\Admin;


/**
 * Admin main page controller.
 * 
 * @since 0.1
 */
class IndexController extends AdminBaseController
{


    use UI\Traits\CommonDataTrait;


    /**
     * Admin dashboard page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['pageTitle'] = __('Administrator dashboard');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // display page alert message if exists.
        if (isset($_SESSION['pageAlertMessage'])) {
            $pageAlertMessage = json_decode($_SESSION['pageAlertMessage'], true);
            $output['pageAlertStatus'] = ($pageAlertMessage['pageAlertStatus'] ?? 'warning');
            $output['pageAlertMessage'] = strip_tags($pageAlertMessage['pageAlertMessage'] ?? '');
            if (isset($pageAlertMessage['pageAlertHttpStatus']) && is_int($pageAlertMessage['pageAlertHttpStatus'])) {
                http_response_code($pageAlertMessage['pageAlertHttpStatus']);
            }
            // also accept page alert dismissable
            if (isset($pageAlertMessage['pageAlertDismissable']) && is_bool($pageAlertMessage['pageAlertDismissable'])) {
                $output['pageAlertDismissable'] = $pageAlertMessage['pageAlertDismissable'];
            }
            unset($_SESSION['pageAlertMessage'], $pageAlertMessage);
        }

        $urlAppBasedPath = $Url->getAppBasedPath(true);
        $output['getDashboardWidgetsUrl'] = $urlAppBasedPath . '/admin/ui/xhr-dashboard-widgets';
        $output['getDashboardWidgetsMethod'] = 'GET';
        $output['orderDashboardWidgetsUrl'] = $urlAppBasedPath . '/admin/ui/xhr-dashboard-widgets';
        $output['orderDashboardWidgetsMethod'] = 'PATCH';
        unset($urlAppBasedPath);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdta', 'rdbaAdminIndex'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaAdminIndex'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaAdminIndex',
                'RdbaAdminIndex',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'getDashboardWidgetsUrl' => $output['getDashboardWidgetsUrl'],
                    'getDashboardWidgetsMethod' => $output['getDashboardWidgetsMethod'],
                    'orderDashboardWidgetsUrl' => $output['orderDashboardWidgetsUrl'],
                    'orderDashboardWidgetsMethod' => $output['orderDashboardWidgetsMethod'],
                ]
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Index/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
